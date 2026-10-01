{{--
    Pratinjau pertanyaan sebuah template formulir.
    Dipakai oleh ikon (?) pada select "Template Formulir" di wizard pembuatan survei.
    $preview = hasil App\Services\TemplateQuestionPreview::build()
--}}
<div class="space-y-6">
    <div class="text-sm text-gray-600 dark:text-gray-400">
        <span class="font-semibold text-gray-900 dark:text-white">{{ $preview['name'] }}</span>
        &middot; versi {{ $preview['version'] }}
        @if ($preview['transport_mode'])
            &middot; {{ $preview['transport_mode'] }}
        @endif
        @if ($preview['description'])
            <p class="mt-1">{{ $preview['description'] }}</p>
        @endif
    </div>

    @if (! empty($preview['fields']))
        <div>
            <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Profil Perjalanan
            </h3>
            <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                @foreach ($preview['fields'] as $field)
                    <li class="flex items-start gap-2">
                        <span class="mt-0.5 text-primary-600 dark:text-primary-400">&bull;</span>
                        <span>
                            {{ $field['label'] }}
                            @if ($field['required'])
                                <span class="text-danger-600 dark:text-danger-400">*</span>
                            @endif
                            <span class="ml-1 text-xs text-gray-400">{{ $field['type'] }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @forelse ($preview['groups'] as $group)
        <div>
            <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ $group['name'] }}
                <span class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-xs font-normal text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    {{ $group['section'] }}
                </span>
            </h3>

            @include('filament.modals.partials.question-group', ['group' => $group])
        </div>
    @empty
        <p class="text-sm italic text-gray-500 dark:text-gray-400">
            Template ini belum memiliki pertanyaan.
        </p>
    @endforelse
</div>
