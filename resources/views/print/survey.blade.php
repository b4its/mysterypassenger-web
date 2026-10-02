<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ $survey->code }}</title>
    <style>
        /* Gaya layar */
        :root { --line: #111; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f4f6; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; color: #111; }

        .toolbar {
            position: sticky; top: 0; z-index: 10;
            display: flex; gap: .5rem; align-items: center; justify-content: flex-end;
            padding: .75rem 1rem; background: #fff; border-bottom: 1px solid #e5e7eb;
        }
        .toolbar .spacer { margin-right: auto; font-weight: 600; }
        .btn {
            appearance: none; border: 1px solid #d1d5db; background: #fff; color: #111;
            padding: .45rem .9rem; border-radius: .375rem; font-size: .875rem; cursor: pointer;
            text-decoration: none;
        }
        .btn-primary { background: #2563eb; border-color: #2563eb; color: #fff; }
        .btn:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }

        .sheet {
            background: #fff; margin: 1.25rem auto; padding: 14mm 10mm;
            width: 297mm; max-width: calc(100% - 2rem);
            box-shadow: 0 1px 3px rgb(0 0 0 / .12);
        }

        h1.doc-title { font-size: 1.05rem; text-align: center; text-transform: uppercase; margin: 0 0 1rem; }
        table.meta { width: 100%; font-size: .8rem; margin-bottom: .75rem; }
        table.meta td.label { width: 190px; font-weight: 700; }
        table.meta td.sep { width: 12px; }

        table.data { width: 100%; border-collapse: collapse; font-size: .78rem; }
        table.data th, table.data td { border: 1px solid var(--line); padding: 5px 6px; vertical-align: top; }
        table.data thead th { background: #eceff1; text-transform: uppercase; font-size: .72rem; text-align: center; }
        .c { text-align: center } .r { text-align: right } .b { font-weight: 700 }
        .photo { max-width: 110px; max-height: 84px; margin: 2px; }
        .photo-empty { color: #6b7280; font-style: italic; font-size: .72rem; }

        table.sign { width: 100%; margin-top: 2rem; font-size: .8rem; }
        table.sign td { text-align: center; vertical-align: top; }
        .sign-space { height: 60px; }
        .sign-line { border-top: 1px dashed var(--line); width: 200px; margin: 0 auto; padding-top: 4px; }

        /* Gaya cetak */
        @media print {
            @page { size: {{ $setting->paper_size }} {{ $setting->orientation }}; margin: 12mm 10mm 16mm; }

            body { background: #fff; }
            .toolbar, .no-print { display: none !important; }
            .sheet { width: auto; max-width: none; margin: 0; padding: 0; box-shadow: none; }

            table.data { font-size: 8.5pt; }
            table.data thead { display: table-header-group; }   /* header terulang per halaman */
            table.data tfoot { display: table-footer-group; }
            table.data tr { page-break-inside: avoid; }
            table.sign { page-break-inside: avoid; }

            a[href]::after { content: ''; }                      /* jangan cetak URL */
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <span class="spacer">{{ $survey->code }} — {{ $title }}</span>

        <a class="btn" href="{{ route('surveys.print', ['survey' => $survey, 'section' => 'checklist', 'auto' => 0]) }}">Ceklist</a>
        <a class="btn" href="{{ route('surveys.print', ['survey' => $survey, 'section' => 'report', 'auto' => 0]) }}">Laporan</a>
        <a class="btn" href="{{ route('surveys.pdf', ['survey' => $survey, 'section' => $section->value, 'download' => 1]) }}">Unduh PDF</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">Cetak</button>
    </div>

    <main class="sheet" role="main">
        @include('pdf.partials.letterhead')

        <h1 class="doc-title">{{ $title }}</h1>

        @include('pdf.partials.meta-table')

        @include($section === \App\Enums\OutputSection::Report
            ? 'print.partials.table-report'
            : 'print.partials.table-checklist')

        @if ($survey->summary_note)
            <section style="margin-top: 1rem; border: 1px solid var(--line); padding: .5rem;">
                <strong>Catatan Ringkasan:</strong><br>
                {!! nl2br(e($survey->summary_note)) !!}
            </section>
        @endif

        @include('pdf.partials.signature')
    </main>

    @if ($autoPrint)
        <script>
            window.addEventListener('load', () => {
                // Tunggu gambar selesai dimuat agar tidak tercetak kosong
                Promise.all(
                    Array.from(document.images)
                        .filter(img => !img.complete)
                        .map(img => new Promise(res => { img.onload = img.onerror = res })),
                ).then(() => window.print())
            })
        </script>
    @endif
</body>
</html>
