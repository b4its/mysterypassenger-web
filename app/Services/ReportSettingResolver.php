<?php

namespace App\Services;

use App\Models\ReportSetting;
use App\Models\TransportMode;

class ReportSettingResolver
{
    /**
     * Ambil pengaturan cetak untuk moda tertentu, fallback ke global (transport_mode_id = null),
     * lalu fallback terakhir ke instance default yang belum disimpan.
     */
    public function resolve(?TransportMode $mode = null): ReportSetting
    {
        if ($mode) {
            $setting = ReportSetting::where('transport_mode_id', $mode->id)->first();

            if ($setting) {
                return $setting;
            }
        }

        return ReportSetting::whereNull('transport_mode_id')->first()
            ?? new ReportSetting;
    }
}
