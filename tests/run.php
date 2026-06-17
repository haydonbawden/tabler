<?php

declare(strict_types=1);

use App\Core\Access;
use App\Core\App;
use App\Core\Auth;
use App\Services\ActivityLogger;
use App\Services\AuditLifecycleService;
use App\Services\CastorDataImporter;
use App\Services\CertificateService;
use App\Services\ClientRecordService;
use App\Services\DashboardService;
use App\Services\EvidenceProgressService;
use App\Services\SearchService;
use App\Services\SettingsService;
use App\Services\TableService;
use App\Services\TableViewService;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$db = App::instance()->database;

$passed = 0;
$failed = 0;

$assert = function (bool $condition, string $message) use (&$passed, &$failed): void {
    if ($condition) {
        $passed++;
        echo "[PASS] {$message}\n";
        return;
    }

    $failed++;
    echo "[FAIL] {$message}\n";
};

$contains = static function (array $results, string $needle): bool {
    foreach ($results as $result) {
        if (str_contains((string) ($result['title'] ?? ''), $needle) || str_contains((string) ($result['meta'] ?? ''), $needle)) {
            return true;
        }
    }
    return false;
};

$arrayHas = static function (array $rows, string $key, mixed $value): bool {
    foreach ($rows as $row) {
        if ((string) ($row[$key] ?? '') === (string) $value) {
            return true;
        }
    }
    return false;
};

$pdo = $db->pdo();
$pdo->beginTransaction();

try {
    $now = date('Y-m-d H:i:s');
    $suffix = bin2hex(random_bytes(4));
    $password = 'ChangeMe123!';

    $db->statement(
        "insert into users (name, email, password_hash, role, status, email_verified_at, created_at, updated_at)
         values (?, ?, ?, 'admin', 'active', ?, ?, ?)",
        ['Phase0 Admin', "phase0.admin.{$suffix}@example.com", password_hash($password, PASSWORD_DEFAULT), $now, $now, $now]
    );
    $adminId = (int) $pdo->lastInsertId();

    $db->statement(
        "insert into users (name, email, password_hash, role, status, email_verified_at, created_at, updated_at)
         values (?, ?, ?, 'auditor', 'active', ?, ?, ?)",
        ['Phase0 Auditor', "phase0.auditor.{$suffix}@example.com", password_hash($password, PASSWORD_DEFAULT), $now, $now, $now]
    );
    $auditorId = (int) $pdo->lastInsertId();

    $db->statement(
        "insert into users (name, email, password_hash, role, status, email_verified_at, created_at, updated_at)
         values (?, ?, ?, 'client', 'active', ?, ?, ?)",
        ['Phase0 Client', "phase0.client.{$suffix}@example.com", password_hash($password, PASSWORD_DEFAULT), $now, $now, $now]
    );
    $clientUserId = (int) $pdo->lastInsertId();

    $db->statement(
        'insert into certification_types (name, code, validity_months, is_active, created_at, updated_at) values (?, ?, 12, 1, ?, ?)',
        ['Phase0 Certification', 'P0-' . $suffix, $now, $now]
    );
    $typeId = (int) $pdo->lastInsertId();

    $db->statement(
        'insert into clients (client_number, abn, legal_name, trading_name, billing_email, status, created_at, updated_at) values (?, ?, ?, ?, ?, ?, ?, ?)',
        ['P0-001', '1111111' . substr($suffix, 0, 4), 'Phase0 Scoped Client ' . $suffix, 'Phase0 Scoped Trading', 'billing@example.com', 'active', $now, $now]
    );
    $clientId = (int) $pdo->lastInsertId();

    $db->statement(
        'insert into clients (client_number, abn, legal_name, status, created_at, updated_at) values (?, ?, ?, ?, ?, ?)',
        ['P0-002', '2222222' . substr($suffix, 0, 4), 'Phase0 Other Client ' . $suffix, 'active', $now, $now]
    );
    $otherClientId = (int) $pdo->lastInsertId();

    $db->statement(
        'insert into contacts (client_id, display_name, email, is_active, receives_reminders, receives_certificates, receives_audit_notifications, created_at, updated_at) values (?, ?, ?, 1, 1, 1, 1, ?, ?)',
        [$clientId, 'Phase0 Primary Contact', "phase0.contact.{$suffix}@example.com", $now, $now]
    );

    $db->statement(
        "insert into audits (audit_number, client_id, certification_type_id, auditor_id, source, status, due_date, created_by, submitted_at, created_at, updated_at)
         values (?, ?, ?, ?, 'manual', 'submitted', curdate(), ?, ?, ?, ?)",
        ['P0-AUD-' . $suffix, $clientId, $typeId, $auditorId, $adminId, $now, $now, $now]
    );
    $auditId = (int) $pdo->lastInsertId();

    $db->statement(
        "insert into audits (audit_number, client_id, certification_type_id, source, status, created_by, created_at, updated_at)
         values (?, ?, ?, 'manual', 'submitted', ?, ?, ?)",
        ['P0-OTHER-' . $suffix, $otherClientId, $typeId, $adminId, $now, $now]
    );
    $otherAuditId = (int) $pdo->lastInsertId();

    $db->statement(
        "insert into audits (audit_number, client_id, certification_type_id, auditor_id, source, status, due_date, created_by, created_at, updated_at)
         values (?, ?, ?, ?, 'manual', 'draft', date_add(curdate(), interval 14 day), ?, ?, ?)",
        ['P0-LIFE-' . $suffix, $clientId, $typeId, $auditorId, $adminId, $now, $now]
    );
    $lifecycleAuditId = (int) $pdo->lastInsertId();

    $db->statement(
        "insert into audits (audit_number, client_id, certification_type_id, auditor_id, source, status, due_date, created_by, created_at, updated_at)
         values (?, ?, ?, ?, 'manual', 'awaiting_evidence', date_add(curdate(), interval 14 day), ?, ?, ?)",
        ['P0-INCOMPLETE-' . $suffix, $clientId, $typeId, $auditorId, $adminId, $now, $now]
    );
    $incompleteAuditId = (int) $pdo->lastInsertId();

    $db->statement(
        'insert into audit_criteria (certification_type_id, section, reference, title, requirement_text, sort_order, is_required, is_active, created_at, updated_at) values (?, ?, ?, ?, ?, 10, 1, 1, ?, ?)',
        [$typeId, 'Governance', 'P0.1', 'Phase0 requirement', 'Provide phase 0 evidence.', $now, $now]
    );
    $criterionId = (int) $pdo->lastInsertId();

    $db->statement(
        "insert into audit_responses (audit_id, audit_criterion_id, client_response, client_status, created_at, updated_at) values (?, ?, 'Lifecycle evidence response', 'submitted', ?, ?)",
        [$lifecycleAuditId, $criterionId, $now, $now]
    );

    $db->statement(
        "insert into audit_responses (audit_id, audit_criterion_id, client_response, client_status, created_at, updated_at) values (?, ?, '', 'in_progress', ?, ?)",
        [$incompleteAuditId, $criterionId, $now, $now]
    );

    $db->statement(
        "insert into audit_responses (audit_id, audit_criterion_id, client_response, client_status, auditor_result, created_at, updated_at) values (?, ?, 'Evidence response', 'submitted', 'acceptable', ?, ?)",
        [$auditId, $criterionId, $now, $now]
    );
    $responseId = (int) $pdo->lastInsertId();

    $db->statement(
        'insert into audit_evidence_files (audit_response_id, uploaded_by, original_filename, stored_path, mime_type, size_bytes, checksum, created_at, updated_at) values (?, ?, ?, ?, ?, 10, ?, ?, ?)',
        [$responseId, $clientUserId, 'phase0.txt', 'storage/evidence/phase0.txt', 'text/plain', hash('sha256', 'phase0'), $now, $now]
    );

    $db->statement(
        "insert into audit_responses (audit_id, audit_criterion_id, client_response, client_status, auditor_result, created_at, updated_at) values (?, ?, 'Other evidence response', 'submitted', 'acceptable', ?, ?)",
        [$otherAuditId, $criterionId, $now, $now]
    );
    $otherResponseId = (int) $pdo->lastInsertId();

    $db->statement(
        'insert into audit_evidence_files (audit_response_id, uploaded_by, original_filename, stored_path, mime_type, size_bytes, checksum, created_at, updated_at) values (?, ?, ?, ?, ?, 10, ?, ?, ?)',
        [$otherResponseId, $clientUserId, 'phase0-other.txt', 'storage/evidence/phase0-other.txt', 'text/plain', hash('sha256', 'phase0-other'), $now, $now]
    );

    $db->statement(
        "insert into certificates (certificate_uid, audit_id, client_id, certification_type_id, certificate_number, issue_number, status, issue_date, expiry_date, last_day_to_renew, validation_token, created_at, updated_at)
         values (?, ?, ?, ?, ?, 1, 'issued', curdate(), date_add(curdate(), interval 30 day), date_add(curdate(), interval 29 day), ?, ?, ?)",
        [bin2hex(random_bytes(16)), $auditId, $clientId, $typeId, 'P0-CERT-' . $suffix, bin2hex(random_bytes(16)), $now, $now]
    );
    $certificateId = (int) $pdo->lastInsertId();

    $db->statement(
        'insert into client_user (client_id, user_id, role_for_client, approved_by, approved_at, created_at, updated_at) values (?, ?, ?, ?, now(), ?, ?)',
        [$clientId, $clientUserId, 'representative', $adminId, $now, $now]
    );

    $db->statement(
        "insert into payments (client_id, certificate_id, audit_id, amount_cents, currency, status, paid_at, created_at, updated_at)
         values (?, ?, ?, 120000, 'AUD', 'paid', ?, ?, ?)",
        [$clientId, $certificateId, $auditId, $now, $now, $now]
    );

    $db->statement(
        "insert into payments (client_id, audit_id, amount_cents, currency, status, created_at, updated_at)
         values (?, ?, 99000, 'AUD', 'paid', ?, ?)",
        [$otherClientId, $otherAuditId, $now, $now]
    );

    $db->statement(
        "insert into email_logs (related_type, related_id, to_email, subject, status, sent_at, created_at, updated_at)
         values ('client', ?, ?, 'Phase0 client email', 'sent', ?, ?, ?)",
        [$clientId, 'billing@example.com', $now, $now, $now]
    );

    $db->statement(
        "insert into email_logs (related_type, related_id, to_email, subject, status, sent_at, created_at, updated_at)
         values ('client', ?, ?, 'Phase0 other client email', 'sent', ?, ?, ?)",
        [$otherClientId, "other.{$suffix}@example.com", $now, $now, $now]
    );

    unset($_SESSION['user_id']);
    $loginSucceeded = Auth::attempt("phase0.admin.{$suffix}@example.com", $password);
    echo "\nAuth and access control\n";
    $assert($loginSucceeded, 'login succeeds with valid password');
    $assert(Auth::id() === $adminId, 'login stores authenticated user id');
    unset($_SESSION['user_id']);
    $assert(!Auth::attempt("phase0.admin.{$suffix}@example.com", 'WrongPassword123!'), 'login fails with invalid password');

    $_SESSION['user_id'] = $clientUserId;
    $assert(Access::canAccessClient($clientId), 'client representative can access linked client');
    $assert(!Access::canAccessClient($otherClientId), 'client representative cannot access another client');
    $assert(Access::canAccessAudit($auditId), 'client representative can access linked audit');
    $assert(!Access::canAccessAudit($otherAuditId), 'client representative cannot access unrelated audit');

    $_SESSION['user_id'] = $auditorId;
    $assert(Access::canAccessClient($clientId), 'auditor can access assigned audit client');
    $assert(!Access::canAccessClient($otherClientId), 'auditor cannot access unassigned client');
    $assert(Access::canAccessAudit($auditId), 'auditor can access assigned audit');
    $assert(!Access::canAccessAudit($otherAuditId), 'auditor cannot access unassigned audit');

    $_SESSION['user_id'] = $adminId;
    $assert(Access::canAccessClient($otherClientId), 'admin can access all clients');
    $assert(Access::canAccessAudit($otherAuditId), 'admin can access all audits');

    echo "\nGlobal search scoping\n";
    $search = new SearchService();
    $_SESSION['user_id'] = $adminId;
    $adminResults = $search->search('Phase0', 'admin');
    $assert($contains($adminResults, 'Phase0 Scoped Client'), 'admin search returns scoped test client');
    $assert($contains($adminResults, 'Phase0 Other Client'), 'admin search returns other test client');

    $_SESSION['user_id'] = $auditorId;
    $auditorResults = $search->search('Phase0', 'auditor');
    $assert($contains($auditorResults, 'Phase0 Scoped Client'), 'auditor search returns assigned client');
    $assert(!$contains($auditorResults, 'Phase0 Other Client'), 'auditor search hides unassigned client');

    $_SESSION['user_id'] = $clientUserId;
    $clientResults = $search->search('Phase0', 'client');
    $assert($contains($clientResults, 'Phase0 Scoped Client'), 'client search returns linked client');
    $assert(!$contains($clientResults, 'Phase0 Other Client'), 'client search hides unrelated client');
    $assert($search->search('', 'client') === [], 'empty search returns no records');

    echo "\nSettings persistence\n";
    $_SESSION['user_id'] = $adminId;
    $settings = new SettingsService();
    $settings->save(['phase0_smoke_setting' => 'ok']);
    $assert(($settings->all()['phase0_smoke_setting'] ?? null) === 'ok', 'settings service persists values');
    $settings->saveUserTheme($adminId, 'dark');
    $assert($settings->userTheme($adminId) === 'dark', 'user theme persists dark preference');
    $settings->saveUserTheme($adminId, 'invalid-theme');
    $assert($settings->userTheme($adminId) === 'light', 'invalid user theme falls back to light');

    echo "\nTables and exports\n";
    $tables = new TableService();
    $clientRows = $tables->rows('clients');
    $assert(count($clientRows) >= 2, 'clients table service returns rows');
    $clientCsv = $tables->csv('clients');
    $assert(str_contains($clientCsv, 'legal_name'), 'clients CSV includes expected header');
    $certRows = $tables->rows('certificates');
    $assert($contains($certRows, 'expiring_soon') || array_key_exists('computed_status', $certRows[0] ?? []), 'certificates table includes computed status');
    $submittedAudits = $tables->rows('audits', ['view' => 'submitted']);
    $assert(count($submittedAudits) >= 2 && count(array_filter($submittedAudits, static fn (array $row): bool => $row['status'] !== 'submitted')) === 0, 'dashboard audit filter returns submitted audits only');
    $expiringCertificates = $tables->rows('certificates', ['view' => 'expiring_90']);
    $assert(count(array_filter($expiringCertificates, static fn (array $row): bool => $row['certificate_number'] === 'P0-CERT-' . $suffix)) === 1, 'dashboard certificate filter returns expiring certificate');
    $activeClients = $tables->rows('clients', ['status' => 'active']);
    $assert(count($activeClients) >= 2 && count(array_filter($activeClients, static fn (array $row): bool => $row['status'] !== 'active')) === 0, 'dashboard client filter returns active clients only');
    $tableViews = new TableViewService();
    $tableViews->savePreferences('clients', ['pageSize' => 50, 'columns' => ['abn' => false], 'filters' => ['status' => 'active']]);
    $preferences = $tableViews->preferences('clients');
    $assert(($preferences['pageSize'] ?? null) === 50 && ($preferences['columns']['abn'] ?? true) === false, 'per-user table preferences persist');
    $tableViews->saveView('clients', 'Phase0 Active Clients ' . $suffix, ['filters' => ['status' => 'active'], 'columns' => ['abn' => false], 'sort' => ['index' => 3, 'direction' => 1]]);
    $views = $tableViews->views('clients');
    $assert(count(array_filter($views, static fn (array $view): bool => $view['name'] === 'Phase0 Active Clients ' . $suffix)) === 1, 'saved table views persist');

    echo "\nDashboard counts and performance\n";
    $dashboard = new DashboardService();
    $adminStats = $dashboard->adminStats();
    $assert((int) $adminStats['total_clients'] === (int) $db->scalar('select count(*) from clients'), 'admin dashboard total clients matches database count');
    $assert((int) $adminStats['submitted'] === (int) $db->scalar("select count(*) from audits where status = 'submitted'"), 'admin dashboard submitted audits matches database count');
    $assert((int) $adminStats['expiring_90'] === (int) $db->scalar("select count(*) from certificates where expiry_date between curdate() and date_add(curdate(), interval 90 day)"), 'admin dashboard expiring certificates matches database count');
    $auditorStats = $dashboard->auditorStats($auditorId);
    $assert((int) $auditorStats['assigned'] === (int) $db->scalar('select count(*) from audits where auditor_id = ?', [$auditorId]), 'auditor dashboard assigned count matches database count');
    $clientStats = $dashboard->clientStats([$clientId]);
    $assert((int) $clientStats['submitted'] === (int) $db->scalar("select count(*) from audits where status = 'submitted' and client_id = ?", [$clientId]), 'client dashboard submitted count matches database count');

    $started = microtime(true);
    $dashboard->adminStats();
    $dashboard->adminQueues();
    $dashboard->auditorStats($auditorId);
    $dashboard->auditorQueue($auditorId);
    $dashboard->clientStats([$clientId]);
    $dashboard->clientActions([$clientId]);
    $dashboard->clientCertificates([$clientId]);
    $tables->rows('clients');
    $tables->rows('audits');
    $tables->rows('certificates');
    $elapsedMs = (microtime(true) - $started) * 1000;
    $assert($elapsedMs < 5000, 'dashboard and table smoke queries complete within 5 seconds on current data (' . number_format($elapsedMs, 1) . ' ms)');

    echo "\nActivity logging\n";
    (new ActivityLogger())->log('phase0_smoke_test', 'Phase 0 smoke test activity.', ['client_id' => $clientId, 'audit_id' => $auditId, 'certificate_id' => $certificateId]);
    (new ActivityLogger())->log('phase0_other_client_activity', 'Other client activity.', ['client_id' => $otherClientId, 'audit_id' => $otherAuditId]);
    $logged = (int) $db->scalar("select count(*) from activity_logs where action = 'phase0_smoke_test' and client_id = ? and audit_id = ? and certificate_id = ?", [$clientId, $auditId, $certificateId]);
    $assert($logged === 1, 'activity logger records context for workflow actions');

    echo "\nClient 360 record scoping\n";
    $clientRecord = (new ClientRecordService())->record($clientId);
    $assert((int) ($clientRecord['client']['id'] ?? 0) === $clientId, 'Client 360 service returns requested client');
    $assert(($clientRecord['summary']['contacts'] ?? 0) === 1, 'Client 360 summary counts scoped contacts');
    $assert($arrayHas($clientRecord['audits'] ?? [], 'id', $auditId), 'Client 360 includes scoped audit');
    $assert(!$arrayHas($clientRecord['audits'] ?? [], 'id', $otherAuditId), 'Client 360 excludes another client audit');
    $assert($arrayHas($clientRecord['certificates'] ?? [], 'id', $certificateId), 'Client 360 includes scoped certificate');
    $assert($arrayHas($clientRecord['evidence'] ?? [], 'original_filename', 'phase0.txt'), 'Client 360 includes scoped evidence');
    $assert(!$arrayHas($clientRecord['evidence'] ?? [], 'original_filename', 'phase0-other.txt'), 'Client 360 excludes another client evidence');
    $assert(count($clientRecord['payments'] ?? []) === 1 && (int) ($clientRecord['payments'][0]['client_id'] ?? 0) === $clientId, 'Client 360 payments are client-scoped');
    $assert($arrayHas($clientRecord['emailLogs'] ?? [], 'subject', 'Phase0 client email'), 'Client 360 includes client email log');
    $assert(!$arrayHas($clientRecord['emailLogs'] ?? [], 'subject', 'Phase0 other client email'), 'Client 360 excludes another client email log');
    $assert($arrayHas($clientRecord['activities'] ?? [], 'action', 'phase0_smoke_test'), 'Client 360 includes scoped activity');
    $assert(!$arrayHas($clientRecord['activities'] ?? [], 'action', 'phase0_other_client_activity'), 'Client 360 excludes another client activity');

    echo "\nEvidence progress service\n";
    $evidenceProgress = new EvidenceProgressService();
    $progress = $evidenceProgress->progress($auditId);
    $assert($progress['total'] === 1 && $progress['required'] === 1, 'evidence progress counts total and required criteria');
    $assert($progress['submitted'] === 1 && $progress['required_submitted'] === 1, 'evidence progress counts submitted criteria');
    $assert($progress['files'] === 1, 'evidence progress counts uploaded files');
    $groupedResponses = $evidenceProgress->groupedResponses($auditId);
    $assert(isset($groupedResponses['Governance']) && count($groupedResponses['Governance']) === 1, 'evidence responses are grouped by section');

    echo "\nAudit lifecycle service\n";
    $lifecycle = new AuditLifecycleService();
    $_SESSION['user_id'] = $adminId;
    $draftActions = array_column($lifecycle->actions($db->first('select * from audits where id = ?', [$lifecycleAuditId]), 'admin', $adminId), 'target');
    $assert(in_array('awaiting_evidence', $draftActions, true), 'admin can request evidence from draft audit');
    try {
        $lifecycle->transition($lifecycleAuditId, 'passed', 'admin', $adminId, 'Invalid jump', true);
        $assert(false, 'invalid lifecycle transition is rejected');
    } catch (Throwable) {
        $assert(true, 'invalid lifecycle transition is rejected');
    }

    $lifecycle->transition($lifecycleAuditId, 'awaiting_evidence', 'admin', $adminId);
    $assert($db->scalar('select status from audits where id = ?', [$lifecycleAuditId]) === 'awaiting_evidence', 'admin transitions draft audit to awaiting evidence');

    $_SESSION['user_id'] = $clientUserId;
    try {
        $lifecycle->transition($incompleteAuditId, 'submitted', 'client', $clientUserId);
        $assert(false, 'client cannot submit incomplete required criteria');
    } catch (Throwable) {
        $assert(true, 'client cannot submit incomplete required criteria');
    }
    $lifecycle->transition($lifecycleAuditId, 'submitted', 'client', $clientUserId);
    $assert($db->scalar('select status from audits where id = ?', [$lifecycleAuditId]) === 'submitted', 'client transitions awaiting evidence audit to submitted');
    $submittedResponses = (int) $db->scalar("select count(*) from audit_responses where audit_id = ? and client_status = 'submitted'", [$lifecycleAuditId]);
    $assert($submittedResponses === 1, 'client submit marks lifecycle audit responses submitted');

    $_SESSION['user_id'] = $auditorId;
    try {
        $lifecycle->transition($otherAuditId, 'in_review', 'auditor', $auditorId);
        $assert(false, 'auditor cannot transition an unassigned audit');
    } catch (Throwable) {
        $assert(true, 'auditor cannot transition an unassigned audit');
    }
    $lifecycle->transition($lifecycleAuditId, 'in_review', 'auditor', $auditorId);
    $assert($db->scalar('select status from audits where id = ?', [$lifecycleAuditId]) === 'in_review', 'assigned auditor starts review');
    try {
        $lifecycle->transition($lifecycleAuditId, 'changes_requested', 'auditor', $auditorId, 'Needs changes without marker.', true);
        $assert(false, 'request changes requires a not acceptable or needs information marker');
    } catch (Throwable) {
        $assert(true, 'request changes requires a not acceptable or needs information marker');
    }
    $db->statement("update audit_responses set auditor_result = 'acceptable', reviewed_by = ?, reviewed_at = now(), updated_at = now() where audit_id = ?", [$auditorId, $lifecycleAuditId]);
    try {
        $lifecycle->transition($lifecycleAuditId, 'passed', 'auditor', $auditorId, '', true);
        $assert(false, 'pass transition requires auditor comment');
    } catch (Throwable) {
        $assert(true, 'pass transition requires auditor comment');
    }
    try {
        $lifecycle->transition($lifecycleAuditId, 'passed', 'auditor', $auditorId, 'Approved without confirmation', false);
        $assert(false, 'pass transition requires confirmation');
    } catch (Throwable) {
        $assert(true, 'pass transition requires confirmation');
    }
    $lifecycle->transition($lifecycleAuditId, 'passed', 'auditor', $auditorId, 'Lifecycle test approved.', true);
    $assert($db->scalar('select status from audits where id = ?', [$lifecycleAuditId]) === 'passed', 'assigned auditor passes audit with comment and confirmation');
    $assert($db->scalar('select result from audits where id = ?', [$lifecycleAuditId]) === 'pass', 'pass lifecycle transition stores audit result');
    $transitionLogs = (int) $db->scalar("select count(*) from activity_logs where action = 'audit_transitioned' and audit_id = ?", [$lifecycleAuditId]);
    $assert($transitionLogs >= 4, 'lifecycle service logs every transition');

    echo "\nWorkflow service availability\n";
    $assert(method_exists(CastorDataImporter::class, 'dryRun'), 'import dry-run service exists');
    $assert(class_exists(CertificateService::class), 'certificate generation service exists');
    $assert(Access::canAccessAudit($auditId), 'evidence download access uses audit access scope');

    $pdo->rollBack();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
} finally {
    unset($_SESSION['user_id']);
}

echo "\n{$passed} passed, {$failed} failed.\n";
exit($failed > 0 ? 1 : 0);
