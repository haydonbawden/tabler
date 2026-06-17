<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use PDO;

final class MigrationRunner
{
    public function migrate(): void
    {
        $db = App::instance()->database;
        $db->statement(
            'create table if not exists migrations (
                id int unsigned auto_increment primary key,
                migration varchar(255) not null unique,
                executed_at timestamp not null default current_timestamp
            ) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci'
        );

        $files = glob(App::instance()->rootPath . '/database/migrations/*.php') ?: [];
        sort($files);

        foreach ($files as $file) {
            $name = basename($file);
            $exists = $db->scalar('select count(*) from migrations where migration = ?', [$name]);
            if ($exists) {
                continue;
            }

            $statements = require $file;
            try {
                foreach ($statements as $statement) {
                    $db->pdo()->exec($statement);
                }
                $db->statement('insert into migrations (migration) values (?)', [$name]);
                echo "Migrated {$name}\n";
            } catch (\Throwable $exception) {
                throw $exception;
            }
        }
    }

    public function seed(): void
    {
        $files = glob(App::instance()->rootPath . '/database/seeders/*.php') ?: [];
        sort($files);
        foreach ($files as $file) {
            (require $file)(App::instance()->database);
            echo 'Seeded ' . basename($file) . "\n";
        }
    }
}
