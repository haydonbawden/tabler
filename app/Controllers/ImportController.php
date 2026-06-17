<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\CastorDataImporter;

final class ImportController extends Controller
{
    public function index(): void
    {
        $runs = $this->db()->select('select import_runs.*, users.name as user_name from import_runs left join users on users.id = import_runs.created_by order by import_runs.created_at desc limit 50');
        $this->view('admin/imports', [
            'title' => 'Imports / Exports',
            'pageSubtitle' => 'Run dry-run validations, commit approved imports, and review import history.',
            'runs' => $runs,
        ] + $this->pageHeader('ADMIN PORTAL', 'Imports / Exports', 'Run dry-run validations, commit approved imports, and review import history.'));
    }

    public function store(): void
    {
        $dryRun = $this->input('dry_run') === '1';
        $path = app('root') . '/reference/castor_data.xlsx';

        if (!empty($_FILES['import_file']['tmp_name']) && is_uploaded_file($_FILES['import_file']['tmp_name'])) {
            $target = app('root') . '/storage/imports/' . date('YmdHis') . '-' . preg_replace('/[^A-Za-z0-9_.-]/', '-', $_FILES['import_file']['name']);
            move_uploaded_file($_FILES['import_file']['tmp_name'], $target);
            $path = $target;
        }

        try {
            $summary = (new CastorDataImporter())->import($path, $dryRun);
            Session::flash('success', ($dryRun ? 'Dry-run validated.' : 'Import completed.') . ' Summary: ' . json_encode($summary));
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        $redirect = (string) $this->input('redirect_to', '/admin/clients');
        redirect(str_starts_with($redirect, '/admin/') ? $redirect : '/admin/clients');
    }
}
