<?php

/**
 * Front controller para cPanel.
 *
 * En hosting compartido public_html es el document root, así que la app vive
 * fuera de él (en ~/netlab) y acá solo queda el contenido de public/.
 * .cpanel.yml copia este archivo como public_html/index.php en cada deploy.
 *
 * Si cambia la ruta de la app, cambiar APP_BASE.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$APP_BASE = __DIR__.'/../netlab';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $APP_BASE.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $APP_BASE.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $APP_BASE.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
