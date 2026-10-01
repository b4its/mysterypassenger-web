<?php

namespace App\Observers;

use App\Models\TemplateField;

class TemplateFieldObserver
{
    public function saved(TemplateField $field): void
    {
        $this->forget();
    }

    public function deleted(TemplateField $field): void
    {
        $this->forget();
    }

    private function forget(): void
    {
        cache()->forget('survey_table_dynamic_fields');
        cache()->forget('survey_export_dynamic_fields');
    }
}
