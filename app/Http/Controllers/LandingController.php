<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    /**
     * Halaman selamat datang publik di "/".
     *
     * Menyajikan tiga pilihan utama sesuai alur aplikasi:
     *  - Membuat laporan (survei baru via web app non-Filament)
     *  - Melihat laporan (daftar & detail survei via web app non-Filament)
     *  - Membuat/mengustomisasi pertanyaan (khusus Administrator di panel Filament)
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
     * @return array<int, array{key: string, title: string, description: string, icon: string, url: string, requiresAuth: bool, adminOnly?: bool}>
     */
    private function actions(): array
    {
        return [
            [
                'key' => 'create-report',
                'title' => 'Buat Laporan',
                'description' => 'Mulai sesi survei baru: pilih moda transportasi dan template aktif, lalu isi kuesioner evaluasi.',
                'icon' => 'plus-circle',
                'url' => route('app.surveys.create'),
                'requiresAuth' => true,
            ],
            [
                'key' => 'view-reports',
                'title' => 'Lihat Laporan',
                'description' => 'Telusuri daftar laporan yang sudah dibuat, lihat detail, pantau status review, dan cetak PDF.',
                'icon' => 'document-text',
                'url' => route('app.surveys.index'),
                'requiresAuth' => true,
            ],
            [
                'key' => 'manage-questions',
                'title' => 'Buat / Kustom Pertanyaan',
                'description' => 'Susun template formulir: indikator, pertanyaan, tipe jawaban, dan bobot. Khusus Administrator di Panel Admin.',
                'icon' => 'adjustments-horizontal',
                'url' => route('filament.admin.resources.form-templates.index'),
                'requiresAuth' => true,
                'adminOnly' => true,
            ],
        ];
    }
}
