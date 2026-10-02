<?php

namespace Tests;

use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Arr;
use Livewire\Features\SupportTesting\Testable;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // Penjaga DIJALANKAN SEBELUM aplikasi booting & RefreshDatabase bekerja,
        // supaya database utama tidak pernah terhapus oleh migrate:fresh.
        $this->guardAgainstUsingMainDatabase();

        parent::setUp();

        // Panel Filament harus aktif agar test resource tidak menemui panel null.
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->overrideFillFormMacro();
    }

    /**
     * Pengaman: test HARUS memakai database uji terpisah (*_test).
     *
     * `php artisan test` mem-boot aplikasi memakai `bootstrap/cache/config.php`
     * bila ada; config ter-cache itu MENGABAIKAN `.env.testing`/phpunit sehingga
     * `RefreshDatabase` akan menghapus data database utama. Karena pengecekan
     * berbasis config penuh hanya valid setelah booting (terlalu lambat), kita
     * deteksi lebih awal dari isi file cache config.
     */
    protected function guardAgainstUsingMainDatabase(): void
    {
        $configCache = dirname(__DIR__).'/bootstrap/cache/config.php';

        if (! is_file($configCache)) {
            return;
        }

        $cached = file_get_contents($configCache) ?: '';

        if (! preg_match_all("/'database'\\s*=>\\s*'([^']+)'/", $cached, $matches)) {
            return;
        }

        $expected = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: null;

        foreach (array_unique($matches[1]) as $db) {
            if ($db !== '' && ! str_ends_with($db, '_test')) {
                $this->fail(sprintf(
                    "Config ter-cache memakai database '%s' (bukan '*_test'), sedangkan test "
                    .'mengharapkan %s. `RefreshDatabase` akan menghapus data DB non-uji. '
                    .'Jalankan `php artisan config:clear` (atau `make test`) lalu ulangi.',
                    $db,
                    $expected ? "'{$expected}'" : 'database uji',
                ));
            }
        }
    }

    /**
     * Filament 5.9 + Livewire 4.4: macro `fillForm()` bawaan memakai
     * `data_set($this, ...)` pada komponen Livewire untuk mengisi state form.
     * Livewire 4 mengembalikan salinan properti lewat __get, sehingga mutasi
     * properti bertingkat tidak persist dan fillForm() menjadi no-op.
     *
     * Kita ganti dengan `->set()` (API resmi Livewire) per path bertitik.
     *
     * @see vendor/filament/forms/src/Testing/TestsForms.php::fillForm()
     */
    protected function overrideFillFormMacro(): void
    {
        Testable::macro('fillForm', function (array|\Closure $state = [], ?string $form = null): static {
            /** @var Testable $this */
            if ($this->instance() instanceof HasActions) {
                $form ??= $this->instance()->getMountedActionSchemaName();
            }

            $form ??= $this->instance()->getDefaultTestingSchemaName();

            $schemaInstance = $this->instance()->{$form};
            $schemaStatePath = $schemaInstance->getStatePath();

            if ($state instanceof \Closure) {
                $state = $state($schemaInstance->getRawState());
            }

            if (is_array($state) && $state !== []) {
                foreach (Arr::dot($state) as $key => $value) {
                    $fullPath = filled($schemaStatePath) ? "{$schemaStatePath}.{$key}" : $key;

                    $this->set($fullPath, $value);
                }
            }

            $this->refresh();

            return $this;
        });
    }
}
