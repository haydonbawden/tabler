<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Auth;

final class SearchService
{
    public function search(string $query, string $role): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $db = App::instance()->database;
        $like = '%' . $query . '%';
        $userId = Auth::id();
        $results = [];

        $clientScope = '';
        $bindings = [];
        if ($role === 'client') {
            $clientScope = ' and clients.id in (select client_id from client_user where user_id = ?)';
            $bindings[] = $userId;
        } elseif ($role === 'auditor') {
            $clientScope = ' and clients.id in (select client_id from audits where auditor_id = ?)';
            $bindings[] = $userId;
        }

        foreach ($db->select(
            "select clients.id, clients.legal_name, clients.trading_name, clients.abn, clients.status from clients
             where (clients.legal_name like ? or clients.trading_name like ? or clients.abn like ?){$clientScope}
             order by clients.legal_name limit 8",
            [$like, $like, $like, ...$bindings]
        ) as $row) {
            $prefix = $role === 'client' ? '/client/profile' : '/admin/clients/' . $row['id'];
            $results[] = ['title' => $row['legal_name'], 'meta' => 'Client · ABN ' . ($row['abn'] ?: 'not recorded') . ' · ' . $row['status'], 'href' => $prefix, 'icon' => 'ti-building-community'];
        }

        $auditScope = '';
        $bindings = [];
        if ($role === 'client') {
            $auditScope = ' and audits.client_id in (select client_id from client_user where user_id = ?)';
            $bindings[] = $userId;
        } elseif ($role === 'auditor') {
            $auditScope = ' and audits.auditor_id = ?';
            $bindings[] = $userId;
        }
        foreach ($db->select(
            "select audits.id, audits.audit_number, audits.status, clients.legal_name as client, certification_types.name as certification
             from audits join clients on clients.id = audits.client_id join certification_types on certification_types.id = audits.certification_type_id
             where audits.audit_number like ?{$auditScope}
             order by audits.updated_at desc limit 8",
            [$like, ...$bindings]
        ) as $row) {
            $base = $role === 'client' ? '/client/audits/' : ($role === 'auditor' ? '/auditor/audits/' : '/admin/audits/');
            $results[] = ['title' => $row['audit_number'] ?: 'Audit #' . $row['id'], 'meta' => 'Audit · ' . $row['client'] . ' · ' . $row['certification'] . ' · ' . $row['status'], 'href' => $base . $row['id'], 'icon' => 'ti-clipboard-list'];
        }

        $certScope = '';
        $bindings = [];
        if ($role === 'client') {
            $certScope = ' and certificates.client_id in (select client_id from client_user where user_id = ?)';
            $bindings[] = $userId;
        } elseif ($role === 'auditor') {
            $certScope = ' and certificates.audit_id in (select id from audits where auditor_id = ?)';
            $bindings[] = $userId;
        }
        foreach ($db->select(
            "select certificates.id, certificates.certificate_number, certificates.status, clients.legal_name as client
             from certificates join clients on clients.id = certificates.client_id
             where certificates.certificate_number like ?{$certScope}
             order by certificates.expiry_date desc limit 8",
            [$like, ...$bindings]
        ) as $row) {
            $base = $role === 'client' ? '/client/certificates/' : '/admin/certificates/';
            $results[] = ['title' => $row['certificate_number'], 'meta' => 'Certificate · ' . $row['client'] . ' · ' . $row['status'], 'href' => $base . $row['id'], 'icon' => 'ti-certificate'];
        }

        if ($role !== 'client') {
            foreach ($db->select(
                "select contacts.id, contacts.display_name, contacts.email, clients.legal_name as client
                 from contacts join clients on clients.id = contacts.client_id
                 where contacts.display_name like ? or contacts.email like ?
                 order by contacts.display_name limit 8",
                [$like, $like]
            ) as $row) {
                $results[] = ['title' => $row['display_name'], 'meta' => 'Contact · ' . $row['client'] . ' · ' . $row['email'], 'href' => '/admin/contacts/' . $row['id'], 'icon' => 'ti-address-book'];
            }
        }

        return array_slice($results, 0, 12);
    }
}
