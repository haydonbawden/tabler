<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class PublicController extends Controller
{
    public function home(): void
    {
        redirect('/login');
    }

    public function certificateForm(): void
    {
        $this->view('auth/verify-certificate', ['title' => 'Verify certificate'], 'layouts/guest');
    }

    public function certificateLookup(): void
    {
        $certificate = $this->db()->first(
            'select certificates.certificate_number, clients.legal_name, certification_types.name as certification, certificates.issue_date, certificates.expiry_date, certificates.status
             from certificates
             join clients on clients.id = certificates.client_id
             join certification_types on certification_types.id = certificates.certification_type_id
             where certificates.certificate_number = ?',
            [trim((string) $this->input('certificate_number'))]
        );

        $this->view('auth/verify-certificate', [
            'title' => 'Verify certificate',
            'certificate' => $certificate,
            'searched' => true,
            'timestamp' => date('Y-m-d H:i:s'),
        ], 'layouts/guest');
    }

    public function stripeWebhook(): void
    {
        http_response_code(202);
        echo 'Stripe renewal workflow is intentionally deferred until the core CRM workflow is complete.';
    }
}
