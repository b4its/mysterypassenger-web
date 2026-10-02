@extends('layouts.app')

@section('title', 'Detail Laporan — ' . $survey->code)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Header & Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <a href="{{ route('app.surveys.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                &larr; Kembali ke Daftar Laporan
            </a>
            <div class="flex items-center gap-3 mt-1">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 font-mono">{{ $survey->code }}</h1>
                @php
                    $statusClass = match($survey->status->value) {
                        'approved' => 'bg-emerald-100 text-emerald-800',
                        'submitted' => 'bg-blue-100 text-blue-800',
                        'under_review' => 'bg-purple-100 text-purple-800',
                        'rejected' => 'bg-rose-100 text-rose-800',
                        default => 'bg-slate-100 text-slate-800',
                    };
                @endphp
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">
                    {{ $survey->status->getLabel() }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Moda: <span class="font-medium text-slate-700">{{ $survey->transportMode->name }}</span> &bull;
                Template: <span class="font-medium text-slate-700">{{ $survey->formTemplate->name }} (v{{ $survey->template_version }})</span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if($survey->status->isEditableBySurveyor() && (auth()->id() === $survey->user_id || auth()->user()->isAdmin()))
                <a href="{{ route('app.surveys.fill', $survey) }}"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Lanjutkan Isi
                </a>
            @endif
            <button type="button"
                    onclick="openPdfPreviewModal('{{ route('surveys.pdf', $survey) }}', '{{ $survey->code }}')"
                    class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition inline-flex items-center gap-1.5 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Pratinjau PDF
            </button>
            <a href="{{ route('surveys.print', $survey) }}" target="_blank"
               class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                Cetak
            </a>
            <a href="{{ route('surveys.pdf', ['survey' => $survey, 'download' => 1]) }}"
               class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                Unduh PDF
            </a>
        </div>
    </div>

    {{-- Review Note Alert if Rejected --}}
    @if(filled($survey->review_note))
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <h3 class="font-bold text-xs uppercase tracking-wider text-amber-900 mb-1">Catatan Reviewer</h3>
            <p class="text-xs text-amber-800 leading-relaxed">{{ $survey->review_note }}</p>
            @if($survey->reviewer)
                <p class="text-[11px] text-amber-700 mt-2">
                    Direview oleh: <strong>{{ $survey->reviewer->name }}</strong>
                    @if($survey->reviewed_at) &bull; {{ $survey->reviewed_at->format('d M Y H:i') }} @endif
                </p>
            @endif
        </div>
    @endif

    {{-- Review Action Panel for Reviewer / Admin --}}
    @if(in_array($survey->status->value, ['submitted', 'under_review'], true) && (auth()->user()->isAdmin() || auth()->user()->isReviewer()))
        <div class="rounded-2xl border border-blue-200 bg-blue-50/50 p-6 shadow-sm space-y-4">
            <div>
                <h3 class="font-bold text-sm text-slate-900">Panel Evaluasi &amp; Persetujuan Laporan</h3>
                <p class="text-xs text-slate-600 mt-0.5">Sebagai Reviewer, Anda dapat menyetujui laporan atau mengembalikannya kepada evaluator bila diperlukan revisi.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 pt-2">
                {{-- Form Approve --}}
                <form method="POST" action="{{ route('app.surveys.review', $survey) }}">
                    @csrf
                    <input type="hidden" name="action" value="approve">
                    <button type="submit"
                            onclick="return confirm('Setujui laporan ini sekarang? Data akan terkunci permanen.');"
                            class="rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition">
                        ✓ Setujui Laporan
                    </button>
                </form>

                {{-- Form Reject --}}
                <form method="POST" action="{{ route('app.surveys.review', $survey) }}" class="flex-1 space-y-2">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    <div class="flex gap-2">
                        <input type="text" name="note" required
                               placeholder="Tuliskan alasan pengembalian/catatan perbaikan..."
                               class="flex-1 rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-900 focus:border-rose-500 focus:outline-none focus:ring-1 focus:ring-rose-500">
                        <button type="submit"
                                onclick="return confirm('Kembalikan laporan ini ke surveyor?');"
                                class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-rose-700 transition">
                            ✕ Kembalikan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Section 1: Ringkasan & Profil Sarana --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Informasi Pelaksanaan &amp; Sarana</h2>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block font-medium">Evaluator</span>
                <span class="text-slate-800 font-semibold">{{ $survey->evaluator_name }}</span>
            </div>
            <div>
                <span class="text-slate-400 block font-medium">Tanggal Pelaksanaan</span>
                <span class="text-slate-800 font-semibold">{{ $survey->executed_at?->format('d F Y, H:i') ?? '-' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block font-medium">Lokasi / Terminal</span>
                <span class="text-slate-800 font-semibold">{{ $survey->location_text ?? '-' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block font-medium">Skor Akhir</span>
                <span class="text-slate-800 font-bold text-sm">
                    {{ $survey->final_score !== null ? number_format($survey->final_score, 1) : '-' }}
                </span>
            </div>
        </div>

        @if($survey->fieldValues->isNotEmpty())
            <div class="pt-3 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Identitas Sarana Transportasi</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                    @foreach($survey->fieldValues as $fv)
                        <div class="rounded-lg bg-slate-50 p-2.5 border border-slate-100">
                            <span class="text-slate-400 block text-[11px]">{{ $fv->field_label ?? $fv->field_key }}</span>
                            <span class="text-slate-800 font-semibold">{{ $fv->value_text ?? (is_array($fv->value) ? implode(', ', $fv->value) : ($fv->value ?? '-')) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(filled($survey->summary_note))
            <div class="pt-3 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Catatan Kesimpulan Evaluator</h3>
                <p class="text-xs text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-100">{{ $survey->summary_note }}</p>
            </div>
        @endif
    </div>

    {{-- Section 2: Hasil Kuesioner & Jawaban --}}
    <div class="space-y-4">
        <h2 class="text-base font-bold text-slate-900">Hasil Evaluasi Kuesioner</h2>

        @if($survey->answers->isEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-xs text-slate-500">
                Belum ada jawaban yang diisi untuk survei ini.
            </div>
        @else
            @php
                $groupedAnswers = $survey->answers->groupBy(fn ($a) => $a->question->group->name ?? 'Lain-lain');
                $qNumber = 1;
            @endphp

            @foreach($groupedAnswers as $groupName => $answers)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                    <h3 class="font-bold text-sm text-slate-900 border-b border-slate-100 pb-2">
                        {{ $groupName }}
                    </h3>

                    <div class="space-y-4 divide-y divide-slate-100">
                        @foreach($answers as $ans)
                            <div class="pt-3 first:pt-0 space-y-2">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="text-xs font-medium text-slate-800">
                                        <span class="font-bold">{{ $qNumber++ }}.</span> {{ $ans->question->text }}
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        @if($ans->question->answer_type->value === 'boolean')
                                            @if($ans->value_boolean === true)
                                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800">Ya / Memenuhi</span>
                                            @elseif($ans->value_boolean === false)
                                                <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-bold text-rose-800">Tidak</span>
                                            @else
                                                <span class="text-slate-400 text-xs">-</span>
                                            @endif
                                        @elseif($ans->question->answer_type->value === 'rating')
                                            <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-700 border border-amber-200">
                                                ★ {{ $ans->value_integer ?? '-' }} / 5
                                            </span>
                                        @else
                                            <span class="text-xs font-semibold text-slate-800">
                                                {{ $ans->displayValue() }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                @if(filled($ans->note))
                                    <div class="text-[11px] text-slate-600 bg-slate-50 px-3 py-1.5 rounded border border-slate-100">
                                        <strong>Catatan:</strong> {{ $ans->note }}
                                    </div>
                                @endif

                                @if($ans->media->isNotEmpty())
                                    <div class="flex flex-wrap gap-2 pt-1">
                                        @foreach($ans->media as $med)
                                            <a href="{{ route('surveys.media', [$survey, $med]) }}" target="_blank"
                                               class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-1 text-[11px] text-slate-700 hover:text-blue-600 border border-slate-200">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ $med->file_name }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
@endsection
