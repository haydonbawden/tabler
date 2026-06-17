<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Auth;

final class TableViewService
{
    public function preferences(string $table): array
    {
        $raw = (new SettingsService())->userSetting($this->preferenceKey($table), '{}');
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function savePreferences(string $table, array $preferences): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        (new SettingsService())->saveUserSetting($userId, $this->preferenceKey($table), json_encode($preferences, JSON_UNESCAPED_SLASHES));
    }

    public function views(string $table): array
    {
        try {
            return App::instance()->database->select(
                "select id, name, scope, filters_json, columns_json, sort_json, is_default
                 from saved_table_views
                 where table_name = ? and (scope = 'shared' or user_id = ?)
                 order by is_default desc, name",
                [$table, Auth::id()]
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public function activeView(string $table, ?int $id): ?array
    {
        if (!$id) {
            return null;
        }

        try {
            return App::instance()->database->first(
                "select id, name, scope, filters_json, columns_json, sort_json, is_default
                 from saved_table_views
                 where id = ? and table_name = ? and (scope = 'shared' or user_id = ?)",
                [$id, $table, Auth::id()]
            );
        } catch (\Throwable) {
            return null;
        }
    }

    public function saveView(string $table, string $name, array $payload, string $scope = 'private'): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        $scope = in_array($scope, ['private', 'shared'], true) ? $scope : 'private';
        App::instance()->database->statement(
            'insert into saved_table_views (user_id, table_name, name, scope, filters_json, columns_json, sort_json, is_default, created_at, updated_at)
             values (?, ?, ?, ?, ?, ?, ?, 0, now(), now())',
            [
                $userId,
                $table,
                $name,
                $scope,
                json_encode($payload['filters'] ?? [], JSON_UNESCAPED_SLASHES),
                json_encode($payload['columns'] ?? [], JSON_UNESCAPED_SLASHES),
                json_encode($payload['sort'] ?? [], JSON_UNESCAPED_SLASHES),
            ]
        );
    }

    private function preferenceKey(string $table): string
    {
        return 'table_preferences.' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $table);
    }
}
