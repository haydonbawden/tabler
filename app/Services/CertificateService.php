<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

final class CertificateService
{
    public function generatePdf(int $certificateId): string
    {
        $db = App::instance()->database;
        $certificate = $db->first(
            'select certificates.*, clients.legal_name, clients.abn, clients.registered_office_address, certification_types.name as certification
             from certificates
             join clients on clients.id = certificates.client_id
             join certification_types on certification_types.id = certificates.certification_type_id
             where certificates.id = ?',
            [$certificateId]
        );

        if (!$certificate) {
            throw new \RuntimeException('Certificate not found.');
        }

        $html = \App\Core\View::render('certificates/template', [
            'certificate' => $certificate,
            'castor' => App::instance()->config->get('app.castor'),
        ], null);

        $storage = App::instance()->rootPath . '/storage/certificates';
        if (!is_dir($storage)) {
            mkdir($storage, 0775, true);
        }

        $filename = 'certificate-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $certificate['certificate_number']) . '.pdf';
        $path = $storage . '/' . $filename;

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            file_put_contents($path, $dompdf->output());
        } else {
            file_put_contents($storage . '/' . basename($filename, '.pdf') . '.html', $html);
            throw new \RuntimeException('Dompdf is not installed. Run composer install before generating PDFs.');
        }

        $relative = 'storage/certificates/' . $filename;
        $db->statement('update certificates set pdf_path = ?, generated_at = now(), updated_at = now() where id = ?', [$relative, $certificateId]);
        (new ActivityLogger())->log('certificate_generated', 'Certificate PDF generated.', ['certificate_id' => $certificateId, 'client_id' => $certificate['client_id']]);

        return $relative;
    }
}
