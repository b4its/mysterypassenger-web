<?php

/**
 * Bootstrap test PHPUnit/Pest.
 *
 * Catatan penting:
 * - Menghapus `bootstrap/cache/config.php` DARI SINI berbahaya: `php artisan
 *   test` sudah mem-boot aplikasi (memakai config ter-cache) SEBELUM file ini
 *   dimuat, sehingga penghapusan di sini membuat state run pertama tidak
 *   konsisten (migrasi parsial → "table doesn't exist" yang flaky).
 * - Karena itu, pembersihan config cache dilakukan SEBELUM artisan booting,
 *   lewat target `make test` (test-prepare). Untuk `php artisan test` langsung,
 *   `Tests\TestCase` memasang penjaga yang menggagalkan test bila mengarah ke
 *   database non-uji (mencegah penghapusan data DB utama).
 *
 * Yang dibersihkan di sini hanya cache hasil Pest/PHPUnit (aman & idempoten).
 */
$phpunitCache = __DIR__.'/../.phpunit.cache';

if (is_dir($phpunitCache)) {
    foreach (glob($phpunitCache.'/*') ?: [] as $file) {
        is_dir($file) ? @rmdir($file) : @unlink($file);
    }
}

require __DIR__.'/../vendor/autoload.php';
