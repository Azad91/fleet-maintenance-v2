<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// NOTE: `ini_set('max_input_vars', ...)` was removed. max_input_vars
// is PHP_INI_PERDIR — it cannot be changed at runtime, so the previous
// call was a silent no-op. The value is now enforced at the INI layer:
//   - Docker/Apache:  see Dockerfile → /usr/local/etc/php/conf.d/
//   - FPM/CGI:        see public/.user.ini
//   - Local CLI:      pass -d max_input_vars=3000 to artisan serve,
//                     or update your local php.ini.

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
