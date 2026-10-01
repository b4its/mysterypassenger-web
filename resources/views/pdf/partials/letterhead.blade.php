<table class="letterhead">
    <tr>
        @if ($logo)
            <td class="logo"><img src="{{ $logo }}" style="width: 52px;" alt=""></td>
        @endif
        <td>
            <div class="org">{{ $setting->organization_name }}</div>
            @foreach ($setting->letterhead_lines ?? [] as $line)
                <div class="lines">{{ is_array($line) ? ($line['text'] ?? '') : $line }}</div>
            @endforeach
        </td>
    </tr>
</table>
