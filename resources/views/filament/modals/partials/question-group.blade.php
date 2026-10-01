{{--
    Render satu indikator (kelompok pertanyaan) beserta sub-indikator & pertanyaannya.
    Rekursif untuk sub-group level 2–3.
    $group = elemen dari TemplateQuestionPreview::build()['groups']
--}}
@if (! empty($group['questions']))
    <ol class="mb-3 list-decimal space-y-1 pl-5 text-sm text-gray-700 dark:text-gray-300">
        @foreach ($group['questions'] as $question)
            <li>
                {{ $question['text'] }}
                @if ($question['code'])
                    <span class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        {{ $question['code'] }}
                    </span>
                @endif
                <span class="ml-1 text-xs text-gray-400">({{ $question['type'] }})</span>
                @if ($question['evidence_required'])
                    <span class="ml-1 text-xs font-medium text-danger-600 dark:text-danger-400">wajib bukti</span>
                @endif
                @if (! empty($question['options']))
                    <ul class="mt-1 list-disc pl-5 text-xs text-gray-500 dark:text-gray-400">
                        @foreach ($question['options'] as $option)
                            <li>
                                {{ $option['label'] }}@if ($option['score'] > 0) — skor {{ rtrim(rtrim(number_format($option['score'], 2, ',', '.'), '0'), ',') }}@endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ol>
@endif

@foreach ($group['children'] as $child)
    <div class="mb-2 ml-4 border-l-2 border-gray-200 pl-3 dark:border-gray-700">
        <h4 class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            {{ $child['name'] }}
        </h4>
        @include('filament.modals.partials.question-group', ['group' => $child])
    </div>
@endforeach
