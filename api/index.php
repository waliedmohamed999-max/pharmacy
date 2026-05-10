<?php

$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';
$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/../public';
$_SERVER['SERVER_SOFTWARE'] = $_SERVER['SERVER_SOFTWARE'] ?? 'vercel-php';
$_ENV['APP_RUNNING_IN_CONSOLE'] = 'false';

require __DIR__ . '/../public/index.php';
