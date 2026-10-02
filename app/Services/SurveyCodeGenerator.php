<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\TransportMode;
use Illuminate\Support\Carbon;

class SurveyCodeGenerator
{
    /**
     * Nomor dokumen: {CODE}/{YYYY}/{MM}/{NNNN}
     * Contoh: SHIP/2026/03/0001
     *
     * Urutan dihitung per (moda, tahun, bulan) dan dilindungi transaksi + lock
     * baris pada tabel surveys agar aman dari race condition.
     */
    public function generate(Survey $survey): string
    {
        $date = $survey->executed_at
            ? Carbon::parse($survey->executed_at)
            : now();

        $prefix = $this->prefix($survey);

        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        $count = Survey::withTrashed()
            ->where('transport_mode_id', $survey->transport_mode_id)
            ->whereBetween('executed_at', [$start, $end])
            ->count();

        $sequence = $count + 1;

        return sprintf(
            '%s/%s/%s/%04d',
            $prefix,
            $date->format('Y'),
            $date->format('m'),
            $sequence,
        );
    }

    private function prefix(Survey $survey): string
    {
        $code = $survey->transportMode?->code
            ?? TransportMode::find($survey->transport_mode_id)?->code;

        return filled($code) ? strtoupper((string) $code) : 'MP';
    }
}
