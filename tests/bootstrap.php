<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
// Responses throw instead of exit() so controllers can be asserted.
App\Core\Response::$testing = true;
