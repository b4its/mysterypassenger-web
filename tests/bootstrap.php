<?php

/**
 * Bootstrap test PHPUnit/Pest.
 *
 * Masalah: bila artefak cache bootstrap ada (config.php, routes-v7.php, dst.),
 * Laravel memakai nilai ter-cache sehingga:
 *   1. `.env.testing` DIABAIKAN → test berjalan pada DB utama dan
 *      `RefreshDatabase` menghapus datanya.
 *   2. Rute Livewire (hash dinamis) tidak cocok → request update 404 dan
 *      state form Filament tidak diterapkan (test seolah "hijau" palsu).
 *
 * Solusi: hapus artefak cache bootstrap sebelum suite berjalan.
 */
$cacheFiles = [
    'config.php',
    'routes-v7.php',
    'events.php',
    'services.php',
    'packages.php',
];

foreach ($cacheFiles as $file) {
    $path = __DIR__.'/../bootstrap/cache/'.$file;

    if (is_file($path)) {
        @unlink($path);
    }
}

// Pest/PHPUnit result cache yang basi dapat memuat test factory lama
// (mis. daftar trait yang usang) dan menyebabkan kegagalan misterius.
$phpunitCache = __DIR__.'/../.phpunit.cache';

if (is_dir($phpunitCache)) {
    foreach (glob($phpunitCache.'/*') ?: [] as $file) {
        is_dir($file) ? @rmdir($file) : @unlink($file);
    }
}

require __DIR__.'/../vendor/autoload.php';
