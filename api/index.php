<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$tmpStorage = '/tmp/laravel-storage';
$directories = [
    $tmpStorage,
    $tmpStorage . '/app',
    $tmpStorage . '/app/public',
    $tmpStorage . '/framework',
    $tmpStorage . '/framework/cache',
    $tmpStorage . '/framework/cache/data',
    $tmpStorage . '/framework/sessions',
    $tmpStorage . '/framework/testing',
    $tmpStorage . '/framework/views',
    $tmpStorage . '/logs',
];

foreach ($directories as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}

$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/../public';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';
$_SERVER['SERVER_SOFTWARE'] = $_SERVER['SERVER_SOFTWARE'] ?? 'vercel-php';
$_ENV['APP_RUNNING_IN_CONSOLE'] = 'false';
$_ENV['VIEW_COMPILED_PATH'] = $_ENV['VIEW_COMPILED_PATH'] ?? $tmpStorage . '/framework/views';
$_SERVER['VIEW_COMPILED_PATH'] = $_SERVER['VIEW_COMPILED_PATH'] ?? $tmpStorage . '/framework/views';
$_ENV['LOG_CHANNEL'] = $_ENV['LOG_CHANNEL'] ?? 'stderr';
$_SERVER['LOG_CHANNEL'] = $_SERVER['LOG_CHANNEL'] ?? 'stderr';

require __DIR__ . '/../vendor/autoload.php';

/** @var Application $app */
$app = require __DIR__ . '/../bootstrap/app.php';
$app->useStoragePath($tmpStorage);

$app->handleRequest(Request::capture());
