@extends('pdf.layouts.document')

@section('content')
<table class="data">
    <thead>
        <tr>
            <th style="width: 4%" rowspan="2">No</th>
            <th style="width: 18%" rowspan="2">Indikator</th>
            <th rowspan="2">Sub Indikator</th>
            <th colspan="2" style="width: 14%">Kondisi</th>
            @if ($showScores)
                <th style="width: 8%" rowspan="2">Skor</th>
            @endif
            @if ($showPhotos)
                <th style="width: 18%" rowspan="2">Foto</th>
            @endif
        </tr>
        <tr>
            <th style="width: 7%">Iya</th>
            <th style="width: 7%">Tidak</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($groups as $group)
            @foreach ($group['rows'] as $i => $row)
                <tr>
                    @if ($i === 0)
                        <td class="c b" rowspan="{{ $group['rowspan'] }}">{{ $group['no'] }}</td>
                        <td class="b" rowspan="{{ $group['rowspan'] }}">{{ $group['name'] }}</td>
                    @endif

                    <td>
                        @if ($row['code'])
                            <span class="b">{{ $row['code'] }}.</span>
                        @endif
                        {{ $row['text'] }}
                        @if ($row['note'])
                            <br><em style="font-size: 8px; color: #444;">Catatan: {{ $row['note'] }}</em>
                        @endif
                    </td>

                    @if ($row['isBoolean'])
                        <td class="c check">{{ $row['isPositive'] ? '✓' : '' }}</td>
                        <td class="c check">{{ $row['isBoolean'] && ! $row['isPositive'] ? '✓' : '' }}</td>
                    @else
                        <td class="c" colspan="2">{{ $row['answer'] }}</td>
                    @endif

                    @if ($showScores)
                        <td class="c nowrap">
                            {{ $row['score'] !== null ? number_format((float) $row['score'], 2, ',', '.') : '-' }}
                        </td>
                    @endif

                    @if ($showPhotos)
                        <td class="c">
                            @forelse ($row['photos'] as $photo)
                                @if ($photo['src'])
                                    <img class="photo" src="{{ $photo['src'] }}" alt="">
                                @endif
                            @empty
                                <span class="photo-empty">Tidak ada</span>
                            @endforelse
                        </td>
                    @endif
                </tr>
            @endforeach
        @empty
            <tr><td colspan="6" class="c">Belum ada jawaban pada bagian ini.</td></tr>
        @endforelse

        @if ($showScores && $groups->isNotEmpty())
            <tr>
                <td colspan="5" class="r b">TOTAL</td>
                <td class="c b">{{ $survey->score_percentage !== null ? number_format((float) $survey->score_percentage, 1, ',', '.').'%' : '-' }}</td>
                <td class="c b">
                    {{ number_format((float) $survey->total_score, 2, ',', '.') }} /
                    {{ number_format((float) $survey->max_score, 2, ',', '.') }}
                </td>
                @if ($showPhotos)
                    <td></td>
                @endif
            </tr>
        @endif
    </tbody>
</table>
@endsection
