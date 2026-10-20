<?php

$temporaryPath = sys_get_temp_dir().'/coignitive-membership';
$storagePath = $temporaryPath.'/storage';

foreach (['app/private', 'app/public', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $directory) {
    $path = $storagePath.'/'.$directory;
    if (! is_dir($path) && ! mkdir($path, 0755, true) && ! is_dir($path)) {
        throw new RuntimeException('Unable to initialize temporary application storage.');
    }
}

$cachePath = $temporaryPath.'/bootstrap/cache';
if (! is_dir($cachePath) && ! mkdir($cachePath, 0755, true) && ! is_dir($cachePath)) {
    throw new RuntimeException('Unable to initialize temporary application cache.');
}

$_ENV['LARAVEL_STORAGE_PATH'] = $storagePath;
$_SERVER['LARAVEL_STORAGE_PATH'] = $storagePath;

require __DIR__.'/../public/index.php';
