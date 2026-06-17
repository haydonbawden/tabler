<?php

declare(strict_types=1);

namespace App\Core;

final class Access
{
    public static function requireRole(array|string $roles): void
    {
        $user = Auth::user();
        $roles = is_array($roles) ? $roles : explode(',', $roles);
        if (!$user || !in_array($user['role'], $roles, true)) {
            http_response_code($user ? 403 : 302);
            if (!$user) {
                redirect('/login');
            }
            echo View::render('errors/403', ['title' => 'Access denied'], 'layouts/app');
            exit;
        }
    }

    public static function canAccessClient(int $clientId): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        if (in_array($user['role'], ['super_admin', 'admin', 'viewer'], true)) {
            return true;
        }

        $db = App::instance()->database;
        if ($user['role'] === 'client') {
            return (bool) $db->scalar('select count(*) from client_user where client_id = ? and user_id = ?', [$clientId, $user['id']]);
        }

        if ($user['role'] === 'auditor') {
            return (bool) $db->scalar('select count(*) from audits where client_id = ? and auditor_id = ?', [$clientId, $user['id']]);
        }

        return false;
    }

    public static function canAccessAudit(int $auditId): bool
    {
        $audit = App::instance()->database->first('select client_id, auditor_id from audits where id = ?', [$auditId]);
        if (!$audit) {
            return false;
        }

        $user = Auth::user();
        if (!$user) {
            return false;
        }
        if (in_array($user['role'], ['super_admin', 'admin', 'viewer'], true)) {
            return true;
        }
        if ($user['role'] === 'auditor' && (int) $audit['auditor_id'] === (int) $user['id']) {
            return true;
        }

        return self::canAccessClient((int) $audit['client_id']);
    }
}
