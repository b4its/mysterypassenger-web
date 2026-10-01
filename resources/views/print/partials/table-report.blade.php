<table class="data">
    <thead>
        <tr>
            <th style="width: 24%">Indikator</th>
            <th>Laporan Hasil</th>
            @if ($showPhotos)
                <th style="width: 20%">Bukti Foto</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse ($groups as $group)
            @foreach ($group['rows'] as $i => $row)
                <tr>
                    @if ($i === 0)
                        <td class="b" rowspan="{{ $group['rowspan'] }}">{{ $group['name'] }}</td>
                    @endif

                    <td>
                        <span class="b">{{ $row['text'] }}</span><br>
                        {!! nl2br(e($row['answer'] !== '-' ? $row['answer'] : '—')) !!}
                        @if ($row['note'])
                            <br><em style="font-size: .72rem; color: #444;">Catatan: {{ $row['note'] }}</em>
                        @endif
                    </td>

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
            <tr><td colspan="3" class="c">Belum ada uraian laporan.</td></tr>
        @endforelse
    </tbody>
</table>
