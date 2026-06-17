<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\TableViewService;

final class TableController extends Controller
{
    public function savePreferences(): void
    {
        $table = preg_replace('/[^A-Za-z0-9_]+/', '', (string) $this->input('table'));
        $payload = json_decode((string) $this->input('preferences', '{}'), true);
        if ($table !== '' && is_array($payload)) {
            (new TableViewService())->savePreferences($table, $payload);
        }

        $this->json(['ok' => true]);
    }

    public function saveView(): void
    {
        $table = preg_replace('/[^A-Za-z0-9_]+/', '', (string) $this->input('table'));
        $name = trim((string) $this->input('name'));
        $payload = json_decode((string) $this->input('payload', '{}'), true);
        $scope = (string) $this->input('scope', 'private');
        if ($table !== '' && $name !== '' && is_array($payload)) {
            (new TableViewService())->saveView($table, $name, $payload, $scope);
        }

        $this->json(['ok' => true]);
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json');
        echo json_encode($payload);
    }
}
