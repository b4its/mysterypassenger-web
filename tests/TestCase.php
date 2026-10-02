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
        parent::setUp();

        // Panel Filament harus aktif agar test resource tidak menemui panel null.
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->guardAgainstUsingMainDatabase();
        $this->overrideFillFormMacro();
    }

    /**
     * Pengaman: test HARUS memakai database uji terpisah (*_test).
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
