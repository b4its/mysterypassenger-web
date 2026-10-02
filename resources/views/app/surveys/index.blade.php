@extends('layouts.app')

@section('title', 'Daftar Laporan')

@section('content')
<div class="space-y-6">
    {{-- Header & Create Action --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Daftar Laporan</h1>
            <p class="text-xs text-slate-500 mt-1">Telusuri seluruh laporan survei evaluasi mutu pelayanan transportasi.</p>
        </div>
        @if(auth()->user()->isAdmin() || auth()->user()->isSurveyor())
            <div>
                <a href="{{ route('app.surveys.create') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Buat Laporan Baru
                </a>
            </div>
        @endif
    </div>

    {{-- Filter Toolbar --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('app.surveys.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                       placeholder="Cari kode dokumen (SHIP/...) atau nama evaluator..."
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="w-full sm:w-48">
                <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st->value }}" {{ ($filters['status'] ?? '') === $st->value ? 'selected' : '' }}>
                            {{ $st->getLabel() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-900 transition">
                    Terapkan
                </button>
                @if(!empty($filters['q']) || !empty($filters['status']))
                    <a href="{{ route('app.surveys.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50 transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Surveys Table --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        @if($surveys->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                </svg>
                Tidak ada laporan yang sesuai dengan kriteria filter.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3">Kode Dokumen</th>
                            <th class="px-6 py-3">Moda Transportasi</th>
                            <th class="px-6 py-3">Template Formulir</th>
                            <th class="px-6 py-3">Tanggal Pelaksanaan</th>
                            <th class="px-6 py-3">Evaluator</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($surveys as $item)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-6 py-4 font-mono font-medium text-slate-900">
                                    <a href="{{ route('app.surveys.show', $item) }}" class="text-blue-600 hover:underline">
                                        {{ $item->code }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                        {{ $item->transportMode->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-700">
                                    {{ $item->formTemplate->name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    {{ $item->executed_at?->format('d M Y') ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-xs font-medium text-slate-700">
                                    {{ $item->evaluator_name }}
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusClass = match($item->status->value) {
                                            'approved' => 'bg-emerald-100 text-emerald-800',
                                            'submitted' => 'bg-blue-100 text-blue-800',
                                            'under_review' => 'bg-purple-100 text-purple-800',
                                            'rejected' => 'bg-rose-100 text-rose-800',
                                            default => 'bg-slate-100 text-slate-800',
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClass }}">
                                        {{ $item->status->getLabel() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3 text-xs font-semibold">
                                        @if($item->status->value === 'draft' && (auth()->id() === $item->user_id || auth()->user()->isAdmin()))
                                            <a href="{{ route('app.surveys.fill', $item) }}" class="text-blue-600 hover:text-blue-800">
                                                Lanjutkan Isi
                                            </a>
                                        @endif
                                        <a href="{{ route('app.surveys.show', $item) }}" class="text-slate-600 hover:text-slate-900">
                                            Detail
                                        </a>
                                        <button type="button"
                                                onclick="openPdfPreviewModal('{{ route('surveys.pdf', $item) }}', '{{ $item->code }}')"
                                                class="text-slate-500 hover:text-slate-700 cursor-pointer">
                                            PDF
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($surveys->hasPages())
                <div class="border-t border-slate-100 px-6 py-4">
                    {{ $surveys->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
