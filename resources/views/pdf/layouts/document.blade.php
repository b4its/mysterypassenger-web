<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — {{ $survey->code }}</title>
    <style>
        @page { margin: 12mm 10mm 16mm 10mm; }

        * { font-family: 'dejavu sans', sans-serif; }
        body { font-size: 9px; color: #000; margin: 0; }

        .letterhead { width: 100%; border-bottom: 1.5px solid #000; padding-bottom: 6px; margin-bottom: 10px; }
        .letterhead td { vertical-align: middle; }
        .letterhead .logo { width: 56px; }
        .letterhead .org { font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .letterhead .lines { font-size: 8px; color: #333; }

        h1.doc-title { font-size: 13px; text-align: center; margin: 0 0 12px; text-transform: uppercase; letter-spacing: .4px; }

        table.meta { width: 100%; margin-bottom: 10px; }
        table.meta td { padding: 1.5px 0; font-size: 9px; }
        table.meta td.label { width: 190px; font-weight: bold; }
        table.meta td.sep { width: 10px; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th,
        table.data td { border: .8px solid #000; padding: 4px 5px; vertical-align: top; }
        table.data thead th { background: #e8e8e8; font-size: 8.5px; text-align: center; text-transform: uppercase; }
        table.data tbody tr { page-break-inside: avoid; }

        .c { text-align: center; }
        .r { text-align: right; }
        .b { font-weight: bold; }
        .nowrap { white-space: nowrap; }

        .badge-yes { font-weight: bold; }
        .badge-no  { font-weight: bold; }

        .photo { max-width: 86px; max-height: 68px; margin: 1px; }
        .photo-empty { font-size: 8px; color: #666; font-style: italic; }

        .summary { margin-top: 10px; border: .8px solid #000; padding: 6px; font-size: 9px; }

        table.sign { width: 100%; margin-top: 26px; }
        table.sign td { text-align: center; vertical-align: top; font-size: 9px; }
        .sign-space { height: 54px; }
        .sign-line { border-top: 1px dashed #000; width: 190px; margin: 0 auto; padding-top: 3px; }

        .footer-note { margin-top: 14px; font-size: 7.5px; color: #555; text-align: center; }
    </style>
</head>
<body>
    @include('pdf.partials.letterhead')

    <h1 class="doc-title">{{ $title }}</h1>

    @include('pdf.partials.meta-table')

    @yield('content')

    @if ($survey->summary_note)
        <div class="summary">
            <span class="b">Catatan Ringkasan:</span><br>
            {!! nl2br(e($survey->summary_note)) !!}
        </div>
    @endif

    @include('pdf.partials.signature')

    @if ($setting->footer_note)
        <div class="footer-note">{{ $setting->footer_note }}</div>
    @endif
</body>
</html>
