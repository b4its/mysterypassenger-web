<x-filament-panels::page>
    @if (! $this->record->isEditable())
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Template ini berstatus <strong>{{ $this->record->status->getLabel() }}</strong> dan tidak dapat diubah.
                Gunakan aksi <em>Versi Baru</em> untuk membuat revisi sebagai draf.
            </p>
        </x-filament::section>
    @endif

    <form wire:submit="save">
        {{ $this->form }}
    </form>
</x-filament-panels::page>
