<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Auth;

final class SettingsService
{
    public const DEFAULTS = [
        'business_name' => 'Castor Audit & Advisory',
        'abn' => '50 638 775 381',
        'support_email' => 'support@castoraustralia.com.au',
        'billing_email' => 'billing@castoraustralia.com.au',
        'registered_address' => '',
        'primary_colour' => '#206bc4',
        'accent_colour' => '#f59f00',
        'logo_url' => '/assets/caa-logo-landscape.png',
        'show_logo' => '1',
        'certificate_validity_months' => '12',
        'renewal_reminder_days' => '90',
        'auto_generate_pdf' => '1',
        'public_verification' => '1',
        'sender_name' => 'Castor Audit & Advisory',
        'sender_email' => 'support@castoraustralia.com.au',
        'send_audit_reminders' => '1',
        'send_expiry_notices' => '1',
        'session_timeout' => '30',
        'login_attempt_limit' => '5',
        'strong_passwords' => '1',
        'require_admin_mfa' => '0',
        'admin_landing_page' => 'clients',
        'records_per_table' => '20',
        'client_self_service_contacts' => '1',
        'show_public_verification_link' => '1',
    ];

    public function all(): array
    {
        try {
            $rows = App::instance()->database->select('select setting_key, setting_value from settings');
        } catch (\Throwable) {
            return self::DEFAULTS;
        }

        $settings = self::DEFAULTS;
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = (string) $row['setting_value'];
        }
        return $settings;
    }

    public function save(array $settings): void
    {
        $db = App::instance()->database;
        foreach ($settings as $key => $value) {
            $db->statement(
                'insert into settings (setting_key, setting_value, setting_type, updated_by, created_at, updated_at)
                 values (?, ?, ?, ?, now(), now())
                 on duplicate key update setting_value = values(setting_value), setting_type = values(setting_type), updated_by = values(updated_by), updated_at = now()',
                [$key, (string) $value, $this->typeFor($key), Auth::id()]
            );
        }
    }

    public function userTheme(?int $userId = null): string
    {
        $userId ??= Auth::id();
        if (!$userId) {
            return 'light';
        }

        try {
            $theme = App::instance()->database->scalar('select setting_value from user_settings where user_id = ? and setting_key = ?', [$userId, 'theme']);
        } catch (\Throwable) {
            return 'light';
        }

        return in_array($theme, ['light', 'dark'], true) ? (string) $theme : 'light';
    }

    public function saveUserTheme(int $userId, string $theme): void
    {
        $theme = in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
        App::instance()->database->statement(
            'insert into user_settings (user_id, setting_key, setting_value, created_at, updated_at)
             values (?, ?, ?, now(), now())
             on duplicate key update setting_value = values(setting_value), updated_at = now()',
            [$userId, 'theme', $theme]
        );
    }

    public function userSetting(string $key, mixed $default = null, ?int $userId = null): mixed
    {
        $userId ??= Auth::id();
        if (!$userId) {
            return $default;
        }

        try {
            $value = App::instance()->database->scalar('select setting_value from user_settings where user_id = ? and setting_key = ?', [$userId, $key]);
        } catch (\Throwable) {
            return $default;
        }

        return $value === false || $value === null ? $default : $value;
    }

    public function saveUserSetting(int $userId, string $key, string $value): void
    {
        App::instance()->database->statement(
            'insert into user_settings (user_id, setting_key, setting_value, created_at, updated_at)
             values (?, ?, ?, now(), now())
             on duplicate key update setting_value = values(setting_value), updated_at = now()',
            [$userId, $key, $value]
        );
    }

    private function typeFor(string $key): string
    {
        return str_contains($key, 'colour') ? 'color' : 'string';
    }
}
