<?php
declare(strict_types=1);
/*
 | Front controller — single entry point for every request.
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Router;
use App\Core\Request;

$debug = config()['app']['debug'];
error_reporting($debug ? E_ALL : 0);
ini_set('display_errors', $debug ? '1' : '0');

$router = new Router();
(require dirname(__DIR__) . '/routes.php')($router);   // register routes
$router->dispatch(Request::capture());
