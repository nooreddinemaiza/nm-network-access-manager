<?php

use Core\File;
use Core\Routing\Http\Request;
use Core\Routing\Router;
use App\Configuration;

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);


define('ROOT_DIR', dirname(__DIR__));
define('APP_DEBUG', true);
define('TIME_ZONE', 'Africa/Casablanca');
date_default_timezone_set(TIME_ZONE);

require_once(ROOT_DIR . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Autoloader.php');
Core\Autoloader::register();

File::init(ROOT_DIR);



$router = Router::create();
$request = Request::create();
if (!Configuration::isComplete()) {
    File::include('routes', 'installation.php', ['router' => $router]);
} else {
    File::include('routes', 'assets.php', ['router' => $router]);
    File::include('routes', 'middlewares.php', ['router' => $router]);
    File::include('routes', 'public.php', ['router' => $router]);
    File::include('routes', 'dashboard.php', ['router' => $router]);
}

$response = $router->dispatch($request);
$response->send();