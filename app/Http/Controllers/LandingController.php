<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    /**
     * Halaman selamat datang publik di "/".
     *
     * Menyajikan tiga pilihan utama sesuai alur aplikasi:
     *  - Membuat laporan (survei baru)
     *  - Melihat laporan (daftar & detail survei)
     *  - Membuat/mengustomisasi pertanyaan (template formulir)
     *
     * Setiap aksi mengarah ke panel admin; bila pengguna belum masuk,
     * Filament otomatis mengarahkan ke halaman login.
     */
    public function __invoke(): View
    {
        return view('landing', [
            'isAuthenticated' => auth()->check(),
            'user' => auth()->user(),
            'actions' => $this->actions(),
        ]);
    }

    /**
     * @return array<int, array{key: string, title: string, description: string, icon: string, url: string, requiresAuth: bool}>
     */
    private function actions(): array
    {
        return [
            [
                'key' => 'create-report',
                'title' => 'Buat Laporan',
                'description' => 'Mulai sesi survei baru: pilih moda transportasi dan template, lalu isi kuesioner.',
                'icon' => 'plus-circle',
                'url' => route('filament.admin.resources.surveys.create'),
                'requiresAuth' => true,
            ],
            [
                'key' => 'view-reports',
                'title' => 'Lihat Laporan',
                'description' => 'Telusuri daftar laporan yang sudah dibuat, lihat detail, cetak, dan ekspor.',
                'icon' => 'document-text',
                'url' => route('filament.admin.resources.surveys.index'),
                'requiresAuth' => true,
            ],
            [
                'key' => 'manage-questions',
                'title' => 'Buat / Kustom Pertanyaan',
                'description' => 'Susun template formulir: indikator, pertanyaan, tipe jawaban, bobot, dan field profil.',
                'icon' => 'adjustments-horizontal',
                'url' => route('filament.admin.resources.form-templates.index'),
                'requiresAuth' => true,
            ],
        ];
    }
}
