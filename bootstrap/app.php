<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Config;
use App\Core\Database;
use App\Core\Env;
use App\Core\Router;
use App\Core\Session;

$root = dirname(__DIR__);

$autoload = $root . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
} else {
    spl_autoload_register(function (string $class) use ($root): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $path = $root . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    });

    require_once $root . '/app/Helpers/functions.php';
}

Env::load($root . '/.env');

$config = new Config([
    'app' => require $root . '/config/app.php',
    'database' => require $root . '/config/database.php',
    'mail' => require $root . '/config/mail.php',
    'stripe' => require $root . '/config/stripe.php',
]);

date_default_timezone_set($config->get('app.timezone', 'Australia/Brisbane'));

Session::start((bool) $config->get('app.session_secure', false));

$database = new Database($config->get('database'));
$router = new Router();

$app = new App($root, $config, $database, $router);

(require $root . '/routes/web.php')($router);

return $app;
