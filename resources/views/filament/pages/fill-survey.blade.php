<x-filament-panels::page>
    <div
        x-data="{
            dirty: false,
            init() {
                window.addEventListener('get-geolocation', () => this.locate())
                window.addEventListener('beforeunload', (e) => {
                    if (this.dirty) { e.preventDefault(); e.returnValue = '' }
                })
            },
            locate() {
                if (! navigator.geolocation) return
                navigator.geolocation.getCurrentPosition((pos) => {
                    $wire.set('data.latitude', pos.coords.latitude.toFixed(7))
                    $wire.set('data.longitude', pos.coords.longitude.toFixed(7))
                })
            },
        }"
        x-on:form-field-updated="dirty = true"
    >
        {{ $this->form }}
    </div>

    @if ($this->record->status === \App\Enums\SurveyStatus::Draft)
        <div wire:poll.60s="autosave" class="text-xs text-gray-500">
            Draf disimpan otomatis setiap 60 detik.
        </div>
    @endif
</x-filament-panels::page>
