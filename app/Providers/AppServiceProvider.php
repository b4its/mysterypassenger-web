<?php

namespace App\Providers;

use App\Listeners\RecordLastLogin;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Proteksi formula injection untuk semua kolom export (dok. 03 §5).
        ExportColumn::configureUsing(fn (ExportColumn $column) => $column->preventFormulaInjection());

        // Export selalu ke disk privat.
        ExportAction::configureUsing(fn (ExportAction $action) => $action->fileDisk('local'));

        // Konfirmasi wajib untuk semua aksi hapus.
        DeleteAction::configureUsing(fn (DeleteAction $action) => $action
            ->requiresConfirmation()
            ->modalHeading('Konfirmasi Hapus')
            ->modalDescription('Data yang dihapus tidak dapat dikembalikan. Lanjutkan?')
            ->modalSubmitActionLabel('Ya, Hapus'));

        // Tanggal Indonesia di seluruh aplikasi.
        Carbon::setLocale('id');

        Event::listen(Login::class, RecordLastLogin::class);

        Model::preventLazyLoading(! app()->isProduction());
        Model::unguard(false);

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
