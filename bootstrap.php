<?php
declare(strict_types=1);
/*
 | Lightweight PSR-4 autoloader + app bootstrap.
 | Lets the app run even before `composer install` (runtime needs no vendor).
 | Composer autoload is used automatically if vendor/ exists.
 */
if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    spl_autoload_register(function (string $class): void {
        foreach (['App\\' => __DIR__ . '/src/', 'Tests\\' => __DIR__ . '/tests/'] as $prefix => $base) {
            if (str_starts_with($class, $prefix)) {
                $rel = str_replace('\\', '/', substr($class, strlen($prefix)));
                $file = $base . $rel . '.php';
                if (is_file($file)) { require $file; return; }
            }
        }
    });
}
require __DIR__ . '/config/config.php';

require __DIR__ . "/src/Core/helpers.php";
