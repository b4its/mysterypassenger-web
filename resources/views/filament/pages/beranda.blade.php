<x-filament-panels::page>
    @php
        $cards = $this->getCards();
        $summary = $this->getSummary();
        $user = auth()->user();
    @endphp

    {{-- Header profil --}}
    <div class="rounded-2xl bg-gradient-to-br from-primary-600 to-primary-800 p-6 text-white shadow-sm">
        <div class="flex items-center gap-4">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-white/20 ring-2 ring-white/40">
                <x-filament::icon icon="heroicon-o-user" class="h-7 w-7" />
            </span>
            <div>
                <p class="text-xl font-bold">Halo, {{ $user?->name }}</p>
                <p class="text-sm text-white/80">{{ $user?->email }}</p>
                @if ($user?->role)
                    <span class="mt-1 inline-block rounded-full bg-white/20 px-2 py-0.5 text-xs font-medium">
                        {{ $user->role->getLabel() }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Ringkasan aktivitas --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([
            ['Total', $summary['total'], 'clipboard-document-list', 'text-primary-600'],
            ['Draf', $summary['draft'], 'pencil-square', 'text-gray-500'],
            ['Terkirim', $summary['submitted'], 'paper-airplane', 'text-info-600'],
            ['Disetujui', $summary['approved'], 'check-badge', 'text-success-600'],
        ] as [$label, $value, $icon, $color])
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                    <x-filament::icon :icon="'heroicon-o-'.$icon" class="h-4 w-4 {{ $color }}" />
                    {{ $label }}
                </div>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($value, 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    {{-- Aksi utama --}}
    <div>
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Aktivitas Utama
        </h2>
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($cards as $card)
                <a href="{{ $card['url'] }}"
                   @class([
                       'group flex items-start gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:bg-gray-900',
                   ])>
                    <span @class([
                        'flex h-12 w-12 shrink-0 items-center justify-center rounded-xl',
                        'bg-primary-50 text-primary-600 dark:bg-primary-500/10' => $card['color'] === 'primary',
                        'bg-success-50 text-success-600 dark:bg-success-500/10' => $card['color'] === 'success',
                        'bg-warning-50 text-warning-600 dark:bg-warning-500/10' => $card['color'] === 'warning',
                        'bg-info-50 text-info-600 dark:bg-info-500/10' => $card['color'] === 'info',
                    ])>
                        <x-filament::icon :icon="'heroicon-o-'.$card['icon']" class="h-6 w-6" />
                    </span>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $card['title'] }}</p>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $card['description'] }}</p>
                    </div>
                    <x-filament::icon icon="heroicon-m-chevron-right"
                        class="h-5 w-5 shrink-0 text-gray-300 transition group-hover:text-primary-500" />
                </a>
            @endforeach
        </div>
    </div>

    {{-- Widget ringkasan (statistik, chart, tabel survei terbaru) --}}
    @foreach ($this->getDashboardWidgets() as $widget)
        @livewire($widget)
    @endforeach
</x-filament-panels::page>
