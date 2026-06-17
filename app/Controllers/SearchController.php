<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Services\SearchService;

final class SearchController extends Controller
{
    public function admin(): void
    {
        $this->json((new SearchService())->search((string) $this->input('q', ''), 'admin'));
    }

    public function auditor(): void
    {
        $this->json((new SearchService())->search((string) $this->input('q', ''), 'auditor'));
    }

    public function client(): void
    {
        $this->json((new SearchService())->search((string) $this->input('q', ''), 'client'));
    }

    private function json(array $results): void
    {
        if (!Auth::user()) {
            http_response_code(401);
            echo json_encode(['results' => []]);
            return;
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results], JSON_THROW_ON_ERROR);
    }
}
