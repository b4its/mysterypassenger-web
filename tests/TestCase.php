<?php

namespace Tests;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Panel Filament harus aktif agar test resource tidak menemui panel null.
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->guardAgainstUsingMainDatabase();
    }

    /**
     * Pengaman: test HARUS memakai database uji terpisah.
     *
     * Bila artefak cache bootstrap (config.php) masih ada, `.env.testing`
     * diabaikan dan test berjalan pada DB utama — `RefreshDatabase` akan
     * mengosongkan datanya. `tests/bootstrap.php` sudah menghapus cache ini,
     * tetapi penjaga ini mencegah kerusakan data bila cache dibuat ulang
     * di tengah suite.
     */
    protected function guardAgainstUsingMainDatabase(): void
    {
        $db = (string) config('database.connections.mysql.database');

        if (app()->environment('testing') && ! str_ends_with($db, '_test')) {
            $this->fail(
                "Test berjalan pada database '{$db}', bukan database uji (*_test). ".
                'Ini akan menghapus data! Jalankan `php artisan config:clear` '.
                '(atau `make test`, yang sudah membersihkannya) lalu ulangi.'
            );
        }
    }
}
