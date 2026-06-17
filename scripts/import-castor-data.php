<?php

declare(strict_types=1);

$app = require dirname(__DIR__) . '/bootstrap/app.php';

$path = $argv[1] ?? $app->rootPath . '/reference/castor_data.xlsx';
$dryRun = in_array('--dry-run', $argv, true);

$result = (new App\Services\CastorDataImporter())->import($path, $dryRun);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
