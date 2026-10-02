@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
<div class="space-y-8">
    {{-- Header / Welcome --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-6 border border-slate-200 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Selamat Datang, {{ $user->name }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $user->organization ?? 'Kementerian Perhubungan' }} &bull; Peran:
                <span class="font-semibold text-slate-700 capitalize">{{ $user->role->value }}</span>
            </p>
        </div>
        <div>
            @if($user->isAdmin() || $user->isSurveyor())
                <a href="{{ route('app.surveys.create') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Mulai Laporan Baru
                </a>
            @endif
        </div>
    </div>

    {{-- Metric Stat Cards --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-medium text-slate-500">Total Laporan</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-bold text-slate-900">{{ $summary['total'] }}</span>
                <span class="text-xs text-slate-400">berkas</span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-medium text-amber-600">Draf / Belum Selesai</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-bold text-amber-600">{{ $summary['draft'] }}</span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-medium text-blue-600">Menunggu Review</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-bold text-blue-600">{{ $summary['submitted'] }}</span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-medium text-emerald-600">Disetujui</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-bold text-emerald-600">{{ $summary['approved'] }}</span>
            </div>
        </div>
    </div>

    {{-- Action Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @if($user->isAdmin() || $user->isSurveyor())
            <a href="{{ route('app.surveys.create') }}"
               class="group flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300 hover:shadow-md transition">
                <div>
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-slate-900">Buat Laporan Baru</h3>
                    <p class="mt-1 text-xs text-slate-500">Pilih moda transportasi dan template aktif untuk mengisi kuesioner evaluasi baru.</p>
                </div>
                <span class="mt-4 text-xs font-semibold text-blue-600 group-hover:underline">Buka formulir &rarr;</span>
            </a>
        @endif

        <a href="{{ route('app.surveys.index') }}"
           class="group flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300 hover:shadow-md transition">
            <div>
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700 group-hover:bg-blue-600 group-hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-slate-900">Daftar Laporan</h3>
                <p class="mt-1 text-xs text-slate-500">Telusuri seluruh riwayat survei, filter status, lihat detail, dan unduh berkas PDF.</p>
            </div>
            <span class="mt-4 text-xs font-semibold text-blue-600 group-hover:underline">Lihat riwayat &rarr;</span>
        </a>

        @if($user->isAdmin() || $user->isReviewer())
            <a href="{{ route('app.surveys.index', ['status' => 'submitted']) }}"
               class="group flex flex-col justify-between rounded-xl border border-blue-200 bg-blue-50/50 p-5 shadow-sm hover:border-blue-400 hover:shadow-md transition">
                <div>
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 text-white transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-slate-900">Antrean Review</h3>
                    <p class="mt-1 text-xs text-slate-600">Periksa laporan yang telah dikirim evaluator, setujui atau kembalikan dengan catatan.</p>
                </div>
                <span class="mt-4 text-xs font-semibold text-blue-700 group-hover:underline">Buka antrean ({{ $summary['submitted'] }}) &rarr;</span>
            </a>
        @endif
    </div>

    {{-- Recent Surveys Table --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="font-bold text-slate-900">Laporan Terkini</h2>
            <a href="{{ route('app.surveys.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                Lihat Semua &rarr;
            </a>
        </div>

        @if($latest->isEmpty())
            <div class="p-8 text-center text-sm text-slate-500">
                Belum ada laporan yang tercatat.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3">Kode Dokumen</th>
                            <th class="px-6 py-3">Moda</th>
                            <th class="px-6 py-3">Tanggal Pelaksanaan</th>
                            <th class="px-6 py-3">Evaluator</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($latest as $item)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-6 py-4 font-mono font-medium text-slate-900">
                                    <a href="{{ route('app.surveys.show', $item) }}" class="text-blue-600 hover:underline">
                                        {{ $item->code }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                        {{ $item->transportMode->name ?? '-' }}
                                    </span>
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
                                    <div class="flex items-center justify-end gap-2 text-xs font-medium">
                                        @if($item->status->value === 'draft' && (auth()->id() === $item->user_id || auth()->user()->isAdmin()))
                                            <a href="{{ route('app.surveys.fill', $item) }}" class="text-blue-600 hover:text-blue-800">
                                                Lanjutkan Isi
                                            </a>
                                        @else
                                            <a href="{{ route('app.surveys.show', $item) }}" class="text-slate-600 hover:text-slate-900">
                                                Detail
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
