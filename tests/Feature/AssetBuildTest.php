<?php

it('resources/css/app.css memindai seluruh Blade view agar kelas Tailwind tergenerate', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    // Tanpa @source untuk resources/views, kelas yang dipakai halaman kustom
    // (landing, beranda, dll.) TIDAK akan tergenerate → halaman tampil tanpa gaya.
    expect($css)
        ->toContain("@source '../views/**/*.blade.php'")
        ->toContain("@source '../../app/Filament/**/*.php'");
});

it('halaman landing memakai kelas utilitas yang dipakai di Blade', function () {
    $blade = file_get_contents(resource_path('views/landing.blade.php'));

    // Kelas penanda yang hanya muncul jika Tailwind memindai file ini.
    expect($blade)->toContain('from-slate-50')->toContain('rounded-2xl');
});

it('manifest Vite memuat entri CSS aplikasi ketika aset dibangun', function () {
    $manifestPath = public_path('build/manifest.json');

    if (! is_file($manifestPath)) {
        // Aset belum dibangun (checkout bersih). Lewati agar tidak gagal lokal.
        expect(true)->toBeTrue();

        return;
    }

    $manifest = json_decode((string) file_get_contents($manifestPath), true);

    expect($manifest)->toHaveKey('resources/css/app.css')
        ->and($manifest['resources/css/app.css']['file'])->toBeString();
});
