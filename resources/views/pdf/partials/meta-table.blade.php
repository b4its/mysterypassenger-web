<table class="meta">
    @foreach ($metaRows as $row)
        <tr>
            <td class="label">{{ $row['label'] }}</td>
            <td class="sep">:</td>
            <td>{{ $row['value'] }}</td>
        </tr>
    @endforeach
</table>
