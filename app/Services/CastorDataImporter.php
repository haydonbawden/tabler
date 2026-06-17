<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class CastorDataImporter
{
    private array $summary = [
        'clients' => ['create' => 0, 'update' => 0, 'skip' => 0, 'errors' => []],
        'contacts' => ['create' => 0, 'update' => 0, 'skip' => 0, 'errors' => []],
        'audits' => ['create' => 0, 'update' => 0, 'skip' => 0, 'errors' => []],
        'certificates' => ['create' => 0, 'update' => 0, 'skip' => 0, 'errors' => []],
    ];
    private array $stagedClients = [];
    private array $stagedAudits = [];

    public function dryRun(string $path): array
    {
        return $this->import($path, true);
    }

    public function import(string $path, bool $dryRun): array
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException('Import file not found: ' . $path);
        }
        if (!class_exists(IOFactory::class)) {
            throw new \RuntimeException('PhpSpreadsheet is not installed. Run composer install before importing XLSX files.');
        }

        $workbook = IOFactory::load($path);
        $sheets = [];
        foreach ($workbook->getWorksheetIterator() as $sheet) {
            $sheets[strtolower(trim($sheet->getTitle()))] = $this->sheetRows($sheet->toArray(null, true, true, true));
        }

        $db = App::instance()->database;
        $pdo = $db->pdo();
        if (!$dryRun) {
            $pdo->beginTransaction();
        }

        try {
            $this->importClients($sheets['clients'] ?? [], $dryRun);
            $this->importContacts($sheets['contacts'] ?? [], $dryRun);
            $this->importAudits($sheets['audits'] ?? [], $dryRun);
            $this->importCertificates($sheets['certificates'] ?? [], $dryRun);

            $db->statement(
                'insert into import_runs (filename, dry_run, status, summary_json, created_by, created_at) values (?, ?, ?, ?, ?, now())',
                [basename($path), $dryRun ? 1 : 0, $dryRun ? 'validated' : 'completed', json_encode($this->summary, JSON_THROW_ON_ERROR), Auth::id()]
            );

            if (!$dryRun) {
                $pdo->commit();
            }
        } catch (\Throwable $exception) {
            if (!$dryRun && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $db->statement(
                'insert into import_runs (filename, dry_run, status, summary_json, error_text, created_by, created_at) values (?, ?, ?, ?, ?, ?, now())',
                [basename($path), $dryRun ? 1 : 0, 'failed', json_encode($this->summary, JSON_THROW_ON_ERROR), $exception->getMessage(), Auth::id()]
            );
            throw $exception;
        }

        return $this->summary;
    }

    private function sheetRows(array $rawRows): array
    {
        $headers = [];
        $rows = [];
        foreach ($rawRows as $index => $rawRow) {
            $values = array_values($rawRow);
            if ($index === 1) {
                $headers = array_map(fn ($value) => $this->key((string) $value), $values);
                continue;
            }

            $row = [];
            foreach ($headers as $offset => $header) {
                if ($header !== '') {
                    $row[$header] = $values[$offset] ?? null;
                }
            }
            if (array_filter($row, static fn ($value) => $value !== null && $value !== '')) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function importClients(array $rows, bool $dryRun): void
    {
        foreach ($rows as $row) {
            $abn = $this->cleanAbn($this->pick($row, ['client_abn', 'abn']));
            $name = $this->pick($row, ['client_name', 'legal_name', 'name', 'client']);
            if (!$name) {
                $this->summary['clients']['errors'][] = 'Client row missing name.';
                continue;
            }

            $existing = $this->clientByAbn($abn);
            $this->summary['clients'][$existing ? 'update' : 'create']++;
            if ($abn) {
                $this->stagedClients[$abn] = ['id' => $existing['id'] ?? 0, 'abn' => $abn, 'legal_name' => $name];
            }
            if ($dryRun) {
                continue;
            }

            if ($existing) {
                App::instance()->database->statement(
                    'update clients set legal_name = ?, client_number = coalesce(?, client_number), updated_at = now() where id = ?',
                    [$name, $this->pick($row, ['client_id', 'client_number', 'id']), $existing['id']]
                );
            } else {
                App::instance()->database->statement(
                    'insert into clients (client_number, abn, legal_name, status, created_at, updated_at) values (?, ?, ?, ?, now(), now())',
                    [$this->pick($row, ['client_id', 'client_number', 'id']), $abn ?: null, $name, 'active']
                );
            }
        }
    }

    private function importContacts(array $rows, bool $dryRun): void
    {
        foreach ($rows as $row) {
            $abn = $this->cleanAbn($this->pick($row, ['client_abn', 'abn']));
            $client = $this->clientByAbn($abn);
            $email = strtolower((string) $this->pick($row, ['email', 'contact_email']));
            $displayName = $this->pick($row, ['display_name', 'contact_name', 'name']) ?: $email;
            $firstName = $this->pick($row, ['first_name']);
            $lastName = $this->pick($row, ['last_name']);
            if (!$client || !$email) {
                $this->summary['contacts']['errors'][] = 'Contact row missing linked client ABN or email.';
                continue;
            }

            $existing = App::instance()->database->first('select id from contacts where client_id = ? and email = ?', [$client['id'], $email]);
            $this->summary['contacts'][$existing ? 'update' : 'create']++;
            if ($dryRun) {
                continue;
            }

            $active = $this->truthy($this->pick($row, ['active', 'is_active', 'status'], 'active'));
            if ($existing) {
                App::instance()->database->statement(
                    'update contacts set first_name = ?, last_name = ?, display_name = ?, is_active = ?, updated_at = now() where id = ?',
                    [$firstName, $lastName, $displayName, $active ? 1 : 0, $existing['id']]
                );
            } else {
                App::instance()->database->statement(
                    'insert into contacts (client_id, first_name, last_name, display_name, email, is_active, created_at, updated_at) values (?, ?, ?, ?, ?, ?, now(), now())',
                    [$client['id'], $firstName, $lastName, $displayName, $email, $active ? 1 : 0]
                );
            }
        }
    }

    private function importAudits(array $rows, bool $dryRun): void
    {
        foreach ($rows as $row) {
            $client = $this->clientByAbn($this->cleanAbn($this->pick($row, ['client_abn', 'abn'])));
            $type = $this->certificationType($this->pick($row, ['audit_type', 'certification', 'certification_type', 'type']));
            $auditNumber = (string) ($this->pick($row, ['audit_id', 'audit_number', 'id']) ?: '');
            if (!$client || !$type || $auditNumber === '') {
                $this->summary['audits']['errors'][] = 'Audit row missing client, certification type or audit number.';
                continue;
            }

            $existing = App::instance()->database->first('select id from audits where audit_number = ?', [$auditNumber]);
            $this->summary['audits'][$existing ? 'update' : 'create']++;
            $this->stagedAudits[$auditNumber] = [
                'id' => $existing['id'] ?? 0,
                'client_id' => $client['id'] ?? 0,
                'certification_type_id' => $type['id'],
                'audit_scope' => $this->pick($row, ['audit_scope', 'scope']),
            ];
            if ($dryRun) {
                continue;
            }

            $status = $this->auditStatus((string) $this->pick($row, ['status'], 'draft'));
            $result = $this->auditResult((string) $this->pick($row, ['result'], $status));
            if ($existing) {
                App::instance()->database->statement(
                    'update audits set client_id = ?, certification_type_id = ?, status = ?, result = ?, start_date = ?, end_date = ?, updated_at = now() where id = ?',
                    [$client['id'], $type['id'], $status, $result, $this->date($this->pick($row, ['start_date', 'audit_date'])), $this->date($this->pick($row, ['end_date'])), $existing['id']]
                );
            } else {
                App::instance()->database->statement(
                    'insert into audits (audit_number, client_id, certification_type_id, source, status, result, start_date, end_date, created_at, updated_at) values (?, ?, ?, ?, ?, ?, ?, ?, now(), now())',
                    [$auditNumber, $client['id'], $type['id'], 'imported', $status, $result, $this->date($this->pick($row, ['start_date', 'audit_date'])), $this->date($this->pick($row, ['end_date']))]
                );
                $this->createAuditResponses((int) App::instance()->database->pdo()->lastInsertId(), (int) $type['id']);
            }
        }
    }

    private function importCertificates(array $rows, bool $dryRun): void
    {
        foreach ($rows as $row) {
            $certificateNumber = (string) $this->pick($row, ['certificate_number', 'cert_number', 'number']);
            $auditNumber = (string) $this->pick($row, ['audit_id', 'audit_number']);
            $audit = $auditNumber ? (App::instance()->database->first('select * from audits where audit_number = ?', [$auditNumber]) ?: ($this->stagedAudits[$auditNumber] ?? null)) : null;
            if (!$audit || $certificateNumber === '') {
                $this->summary['certificates']['errors'][] = 'Certificate row missing linked audit or certificate number.';
                continue;
            }

            $existing = App::instance()->database->first('select id from certificates where certificate_number = ?', [$certificateNumber]);
            $this->summary['certificates'][$existing ? 'update' : 'create']++;
            if ($dryRun) {
                continue;
            }

            $issueDate = $this->date($this->pick($row, ['issue_date', 'issued_at'])) ?: date('Y-m-d');
            $expiryDate = $this->date($this->pick($row, ['expiry_date', 'expires_at'])) ?: date('Y-m-d', strtotime('+12 months', strtotime($issueDate)));
            $values = [
                $audit['id'],
                $audit['client_id'],
                $audit['certification_type_id'],
                $certificateNumber,
                (int) ($this->pick($row, ['issue_number', 'version'], 1) ?: 1),
                $this->certificateStatus((string) $this->pick($row, ['status'], 'issued')),
                $issueDate,
                $expiryDate,
                date('Y-m-d', strtotime('-1 day', strtotime($expiryDate))),
                $audit['audit_scope'] ?? null,
            ];

            if ($existing) {
                App::instance()->database->statement(
                    'update certificates set audit_id = ?, client_id = ?, certification_type_id = ?, certificate_number = ?, issue_number = ?, status = ?, issue_date = ?, expiry_date = ?, last_day_to_renew = ?, audit_scope = ?, updated_at = now() where id = ?',
                    [...$values, $existing['id']]
                );
            } else {
                App::instance()->database->statement(
                    'insert into certificates (certificate_uid, audit_id, client_id, certification_type_id, certificate_number, issue_number, status, issue_date, expiry_date, last_day_to_renew, audit_scope, validation_token, created_at, updated_at)
                     values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now(), now())',
                    [$this->pick($row, ['certificate_uid', 'uid']) ?: bin2hex(random_bytes(16)), ...$values, bin2hex(random_bytes(24))]
                );
            }
        }
    }

    private function createAuditResponses(int $auditId, int $certificationTypeId): void
    {
        $criteria = App::instance()->database->select('select id from audit_criteria where certification_type_id = ? and is_active = 1', [$certificationTypeId]);
        foreach ($criteria as $criterion) {
            App::instance()->database->statement(
                'insert ignore into audit_responses (audit_id, audit_criterion_id, created_at, updated_at) values (?, ?, now(), now())',
                [$auditId, $criterion['id']]
            );
        }
    }

    private function clientByAbn(?string $abn): ?array
    {
        if (!$abn) {
            return null;
        }

        return App::instance()->database->first('select * from clients where abn = ?', [$abn]) ?: ($this->stagedClients[$abn] ?? null);
    }

    private function certificationType(?string $name): ?array
    {
        if (!$name) {
            return App::instance()->database->first('select * from certification_types order by id limit 1');
        }
        $normalized = '%' . strtolower(trim($name)) . '%';
        return App::instance()->database->first('select * from certification_types where lower(name) like ? or lower(code) = ? limit 1', [$normalized, strtolower(trim($name))]);
    }

    private function pick(array $row, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            $normalized = $this->key($key);
            if (array_key_exists($normalized, $row) && $row[$normalized] !== null && $row[$normalized] !== '') {
                return $row[$normalized];
            }
        }

        return $default;
    }

    private function key(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($value)), '_');
    }

    private function cleanAbn(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        return $digits === '' ? null : $digits;
    }

    private function truthy(mixed $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'y', 'active'], true);
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return gmdate('Y-m-d', ((int) $value - 25569) * 86400);
        }
        $time = strtotime((string) $value);
        return $time ? date('Y-m-d', $time) : null;
    }

    private function auditStatus(string $status): string
    {
        $status = strtolower(str_replace([' ', '-'], '_', trim($status)));
        return in_array($status, ['draft', 'awaiting_evidence', 'submitted', 'in_review', 'changes_requested', 'passed', 'failed', 'cancelled'], true) ? $status : 'draft';
    }

    private function auditResult(string $result): ?string
    {
        $result = strtolower(trim($result));
        return in_array($result, ['pass', 'fail'], true) ? $result : null;
    }

    private function certificateStatus(string $status): string
    {
        $status = strtolower(str_replace([' ', '-'], '_', trim($status)));
        return in_array($status, ['draft', 'issued', 'replaced', 'revoked'], true) ? $status : 'issued';
    }
}
