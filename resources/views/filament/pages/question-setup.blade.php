<x-filament-panels::page>
    @php
        $items = $this->getSetupItems();
        $stats = $this->getStats();
    @endphp

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Kelola struktur formulir pelaporan. Susun <strong>indikator</strong> (mis. Tangibles, Reliability),
            lalu tambahkan <strong>pertanyaan</strong> dengan tipe jawaban, bobot, dan kewajiban bukti foto.
        </p>
        <div class="mt-4 flex flex-wrap gap-4 text-sm">
            <span class="inline-flex items-center gap-2 text-gray-700 dark:text-gray-300">
                <x-filament::icon icon="heroicon-o-document-duplicate" class="h-4 w-4 text-success-600" />
                {{ number_format($stats['templates'], 0, ',', '.') }} template terbit
            </span>
            <span class="inline-flex items-center gap-2 text-gray-700 dark:text-gray-300">
                <x-filament::icon icon="heroicon-o-rectangle-stack" class="h-4 w-4 text-warning-600" />
                {{ number_format($stats['groups'], 0, ',', '.') }} indikator
            </span>
            <span class="inline-flex items-center gap-2 text-gray-700 dark:text-gray-300">
                <x-filament::icon icon="heroicon-o-question-mark-circle" class="h-4 w-4 text-primary-600" />
                {{ number_format($stats['questions'], 0, ',', '.') }} pertanyaan
            </span>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($items as $item)
            <a href="{{ $item['url'] }}"
               class="group flex items-start gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:bg-gray-900">
                <span @class([
                    'flex h-12 w-12 shrink-0 items-center justify-center rounded-xl',
                    'bg-primary-50 text-primary-600 dark:bg-primary-500/10' => $item['color'] === 'primary',
                    'bg-success-50 text-success-600 dark:bg-success-500/10' => $item['color'] === 'success',
                    'bg-warning-50 text-warning-600 dark:bg-warning-500/10' => $item['color'] === 'warning',
                    'bg-info-50 text-info-600 dark:bg-info-500/10' => $item['color'] === 'info',
                ])>
                    <x-filament::icon :icon="'heroicon-o-'.$item['icon']" class="h-6 w-6" />
                </span>
                <div class="flex-1">
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $item['title'] }}</p>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $item['description'] }}</p>
                </div>
                <x-filament::icon icon="heroicon-m-chevron-right"
                    class="h-5 w-5 shrink-0 text-gray-300 transition group-hover:text-primary-500" />
            </a>
        @endforeach
    </div>
</x-filament-panels::page>
