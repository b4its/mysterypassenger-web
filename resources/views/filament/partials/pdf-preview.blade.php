{{--
    Konten modal pratinjau PDF untuk panel Filament.
    Parameter:
      $url      — URL rute PDF (stream inline).
      $filename — nama berkas (opsional, untuk tombol Unduh).
    PDF dirender via <iframe> sehingga tampil sebagai pratinjau, bukan unduhan.
--}}
<div class="w-full">
    <iframe
        src="{{ $url }}"
        title="Pratinjau PDF"
        class="h-[75vh] w-full rounded-lg border border-gray-200 bg-white dark:border-gray-700"
        loading="lazy"
    ></iframe>

    @isset($filename)
        <p class="mt-2 text-right text-xs text-gray-500 dark:text-gray-400">
            {{ $filename }}
        </p>
    @endisset
</div>
