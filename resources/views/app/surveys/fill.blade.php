@extends('layouts.app')

@section('title', 'Isi Laporan — ' . $survey->code)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Header Info --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <a href="{{ route('app.surveys.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                &larr; Kembali ke Daftar Laporan
            </a>
            <div class="flex items-center gap-3 mt-1">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 font-mono">{{ $survey->code }}</h1>
                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                    {{ $survey->status->getLabel() }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Moda: <span class="font-medium text-slate-700">{{ $survey->transportMode->name }}</span> &bull;
                Template: <span class="font-medium text-slate-700">{{ $template->name }} (v{{ $survey->template_version }})</span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('surveys.pdf', $survey) }}" target="_blank" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition">
                Pratinjau PDF
            </a>
        </div>
    </div>

    @if($survey->status->value === 'rejected' && filled($survey->review_note))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-900 shadow-sm">
            <div class="font-bold mb-1">Catatan Pengembalian dari Reviewer:</div>
            <p>{{ $survey->review_note }}</p>
            <p class="mt-2 text-rose-700 font-medium">Silakan perbaiki data di bawah lalu kirim ulang laporan.</p>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('app.surveys.update', $survey) }}" enctype="multipart/form-data" class="space-y-8">
        @csrf

        {{-- Section: Data Pelaksanaan & Profil --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">
                1. Data Pelaksanaan &amp; Sarana Transportasi
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Nama Evaluator</label>
                    <input type="text" name="evaluator_name" required
                           value="{{ old('evaluator_name', $survey->evaluator_name) }}"
                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700">Waktu Pelaksanaan</label>
                    <input type="datetime-local" name="executed_at" required
                           value="{{ old('executed_at', $survey->executed_at?->format('Y-m-d\TH:i')) }}"
                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700">Lokasi / Terminal / Pelabuhan</label>
                <input type="text" name="location_text"
                       value="{{ old('location_text', $survey->location_text) }}"
                       placeholder="mis. Pelabuhan Tanjung Priok"
                       class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            {{-- Dynamic Template Fields --}}
            @if($template->fields->isNotEmpty())
                <div class="pt-4 border-t border-slate-100">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Field Identitas Sarana</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($template->fields as $field)
                            @php
                                $val = $values['fields'][$field->key] ?? old("fields.{$field->key}", $field->default_value);
                            @endphp
                            <div>
                                <label class="block text-xs font-semibold text-slate-700">
                                    {{ $field->label }}
                                    @if($field->is_required) <span class="text-rose-500">*</span> @endif
                                </label>
                                @if($field->field_type->value === 'select')
                                    <select name="fields[{{ $field->key }}]" {{ $field->is_required ? 'required' : '' }}
                                            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        <option value="">-- Pilih --</option>
                                        @foreach($field->options ?? [] as $optVal => $optLabel)
                                            <option value="{{ $optVal }}" {{ (string)$val === (string)$optVal ? 'selected' : '' }}>
                                                {{ $optLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="{{ $field->field_type->value === 'number' ? 'number' : ($field->field_type->value === 'date' ? 'date' : 'text') }}"
                                           name="fields[{{ $field->key }}]"
                                           value="{{ $val }}"
                                           placeholder="{{ $field->placeholder ?? '' }}"
                                           {{ $field->is_required ? 'required' : '' }}
                                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                @endif
                                @if(filled($field->helper_text))
                                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $field->helper_text }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Section: Kuesioner Pertanyaan Berdasarkan Indikator --}}
        <div class="space-y-6">
            <h2 class="text-lg font-bold text-slate-900">2. Lembar Kuesioner &amp; Penilaian Mutu</h2>

            @php $qIndex = 1; @endphp
            @foreach($template->rootGroups as $group)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
                    <div class="border-b border-slate-100 pb-3">
                        <h3 class="font-bold text-base text-slate-900 flex items-center gap-2">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">
                                {{ $loop->iteration }}
                            </span>
                            {{ $group->name }}
                        </h3>
                        @if(filled($group->description))
                            <p class="text-xs text-slate-500 mt-1">{{ $group->description }}</p>
                        @endif
                    </div>

                    {{-- Pertanyaan di dalam grup ini --}}
                    @if($group->questions->isNotEmpty())
                        <div class="space-y-6 divide-y divide-slate-100">
                            @foreach($group->questions as $question)
                                @php
                                    $currVal = $values['answers'][$question->id] ?? null;
                                    $currNote = $values['notes'][$question->id] ?? '';
                                    $currMedia = $values['media'][$question->id] ?? [];
                                @endphp
                                <div class="pt-4 first:pt-0 space-y-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-bold text-slate-800">
                                                {{ $qIndex++ }}. {{ $question->text }}
                                                @if($question->is_required)
                                                    <span class="text-rose-500">*</span>
                                                @endif
                                            </p>
                                            @if(filled($question->guidance))
                                                <p class="text-[11px] text-slate-500 mt-0.5">{{ $question->guidance }}</p>
                                            @endif
                                        </div>
                                        <span class="inline-flex rounded bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600">
                                            {{ $question->answer_type->getLabel() }}
                                        </span>
                                    </div>

                                    {{-- Answer Input Components --}}
                                    <div class="pl-4 border-l-2 border-slate-100 space-y-3">
                                        @if($question->answer_type->value === 'boolean')
                                            <div class="flex items-center gap-6 text-xs">
                                                <label class="flex items-center gap-2 cursor-pointer font-medium text-slate-700">
                                                    <input type="radio" name="answers[{{ $question->id }}]" value="1"
                                                           {{ $currVal === true || (string)$currVal === '1' ? 'checked' : '' }}
                                                           class="text-blue-600 focus:ring-blue-500">
                                                    <span>Ya / Memenuhi Syarat</span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer font-medium text-slate-700">
                                                    <input type="radio" name="answers[{{ $question->id }}]" value="0"
                                                           {{ $currVal === false || (string)$currVal === '0' ? 'checked' : '' }}
                                                           class="text-rose-600 focus:ring-rose-500">
                                                    <span>Tidak / Belum Memenuhi</span>
                                                </label>
                                            </div>
                                        @elseif($question->answer_type->value === 'rating')
                                            <div class="flex items-center gap-4">
                                                @for($r = 1; $r <= 5; $r++)
                                                    <label class="flex flex-col items-center gap-1 cursor-pointer">
                                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $r }}"
                                                               {{ (int)$currVal === $r ? 'checked' : '' }}
                                                               class="text-amber-500 focus:ring-amber-500">
                                                        <span class="text-xs font-semibold text-slate-700">{{ $r }}</span>
                                                    </label>
                                                @endfor
                                            </div>
                                        @elseif($question->answer_type->value === 'select_single')
                                            <select name="answers[{{ $question->id }}]"
                                                    class="block w-full max-w-md rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                <option value="">-- Pilih Jawaban --</option>
                                                @foreach($question->questionOptions as $opt)
                                                    <option value="{{ $opt->value }}" {{ (string)$currVal === (string)$opt->value ? 'selected' : '' }}>
                                                        {{ $opt->label }} (Skor: {{ $opt->score }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        @elseif(in_array($question->answer_type->value, ['text', 'textarea']))
                                            <textarea name="answers[{{ $question->id }}]" rows="2"
                                                      placeholder="Tuliskan uraian hasil evaluasi..."
                                                      class="block w-full rounded-lg border border-slate-300 p-2.5 text-xs text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">{{ $currVal }}</textarea>
                                        @else
                                            <input type="text" name="answers[{{ $question->id }}]" value="{{ $currVal }}"
                                                   class="block w-full max-w-md rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        @endif

                                        {{-- Catatan Tambahan --}}
                                        <div>
                                            <input type="text" name="notes[{{ $question->id }}]" value="{{ $currNote }}"
                                                   placeholder="Catatan tambahan (opsional)..."
                                                   class="block w-full max-w-lg rounded-md border border-slate-200 px-2.5 py-1 text-[11px] text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        </div>

                                        {{-- Unggah Bukti Foto --}}
                                        <div class="pt-1">
                                            <label class="block text-[11px] font-medium text-slate-500 mb-1">
                                                Unggah Foto Bukti (Opsional)
                                            </label>
                                            <input type="file" name="media[{{ $question->id }}][]" multiple accept="image/*"
                                                   class="block w-full text-[11px] text-slate-500 file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-[11px] file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">

                                            @if(!empty($currMedia))
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    @foreach($currMedia as $med)
                                                        <a href="{{ route('surveys.media', [$survey, $med]) }}" target="_blank"
                                                           class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-[10px] text-slate-600 hover:text-blue-600">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                            {{ $med->file_name }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Sub-grup bila ada --}}
                    @foreach($group->children as $child)
                        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4 space-y-4">
                            <h4 class="font-semibold text-xs text-slate-800">{{ $child->name }}</h4>
                            @foreach($child->questions as $childQ)
                                @php
                                    $currVal = $values['answers'][$childQ->id] ?? null;
                                    $currNote = $values['notes'][$childQ->id] ?? '';
                                    $currMedia = $values['media'][$childQ->id] ?? [];
                                @endphp
                                <div class="space-y-2 bg-white p-3 rounded-lg border border-slate-200">
                                    <p class="text-xs font-bold text-slate-800">{{ $qIndex++ }}. {{ $childQ->text }}</p>
                                    @if($childQ->answer_type->value === 'boolean')
                                        <div class="flex items-center gap-6 text-xs">
                                            <label class="flex items-center gap-2 cursor-pointer font-medium text-slate-700">
                                                <input type="radio" name="answers[{{ $childQ->id }}]" value="1"
                                                       {{ $currVal === true || (string)$currVal === '1' ? 'checked' : '' }}
                                                       class="text-blue-600 focus:ring-blue-500">
                                                <span>Ya / Memenuhi</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer font-medium text-slate-700">
                                                <input type="radio" name="answers[{{ $childQ->id }}]" value="0"
                                                       {{ $currVal === false || (string)$currVal === '0' ? 'checked' : '' }}
                                                       class="text-rose-600 focus:ring-rose-500">
                                                <span>Tidak</span>
                                            </label>
                                        </div>
                                    @else
                                        <textarea name="answers[{{ $childQ->id }}]" rows="2" placeholder="Uraian hasil..."
                                                  class="block w-full rounded-md border border-slate-300 p-2 text-xs">{{ $currVal }}</textarea>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        {{-- Ringkasan Catatan Umum --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-3">
            <h3 class="font-bold text-sm text-slate-900">Catatan Kesimpulan / Catatan Umum Evaluasi</h3>
            <textarea name="summary_note" rows="3"
                      placeholder="Tuliskan catatan umum mengenai kondisi fasilitas, kesesuaian prosedur, atau rekomendasi perbaikan..."
                      class="block w-full rounded-xl border border-slate-300 p-3 text-xs text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">{{ old('summary_note', $survey->summary_note) }}</textarea>
        </div>

        {{-- Action Bar --}}
        <div class="sticky bottom-4 z-20 flex items-center justify-between rounded-2xl border border-slate-200 bg-white/95 backdrop-blur p-4 shadow-lg">
            <div class="text-xs text-slate-500">
                Status saat ini: <strong class="text-slate-700">{{ $survey->status->getLabel() }}</strong>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" name="submit" value="0"
                        class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                    Simpan Draf
                </button>
                <button type="submit" name="submit" value="1"
                        onclick="return confirm('Kirim laporan ini sekarang? Setelah dikirim, Anda tidak dapat mengubah data hingga laporan disetujui atau dikembalikan oleh reviewer.');"
                        class="rounded-xl bg-blue-600 px-6 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 transition">
                    Kirim Laporan &rarr;
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
