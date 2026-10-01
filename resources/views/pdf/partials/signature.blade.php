<table class="sign">
    <tr>
        <td style="width: {{ 100 - (count($signatures) * 28) }}%"></td>
        @foreach ($signatures as $sign)
            <td style="width: 28%">
                {{ $survey->executed_at?->translatedFormat('l, d - F - Y') }}<br>
                <span class="b">{{ $sign['label'] }}</span>
                <div class="sign-space"></div>
                <div class="sign-line">
                    <span class="b">{{ $sign['name'] }}</span>
                    @if ($sign['position'])
                        <br><span style="font-size: 8px;">{{ $sign['position'] }}</span>
                    @endif
                </div>
            </td>
        @endforeach
    </tr>
</table>
