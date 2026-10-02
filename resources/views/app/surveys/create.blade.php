@extends('layouts.app')

@section('title', 'Buat Laporan Baru')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="{{ route('app.surveys.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
            &larr; Kembali ke Daftar Laporan
        </a>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 mt-2">Mulai Laporan Baru</h1>
        <p class="text-xs text-slate-500 mt-1">Pilih moda transportasi dan template aktif untuk membuat sesi evaluasi baru.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        @if($modes->isEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">
                Belum ada template formulir aktif yang tersedia untuk penugasan akun Anda. Silakan hubungi Administrator.
            </div>
        @else
            <form method="POST" action="{{ route('app.surveys.store') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="form_template_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                        Moda &amp; Template Formulir <span class="text-rose-500">*</span>
                    </label>
                    <p class="text-[11px] text-slate-400 mt-0.5 mb-2">Pilih template yang sesuai dengan sarana transportasi yang sedang dievaluasi.</p>
                    <select id="form_template_id" name="form_template_id" required
                            class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">-- Pilih Template Formulir --</option>
                        @foreach($modes as $mode)
                            <optgroup label="Moda: {{ $mode->name }}">
                                @foreach($templatesByMode[$mode->id] ?? [] as $tmpl)
                                    <option value="{{ $tmpl->id }}" {{ old('form_template_id') == $tmpl->id ? 'selected' : '' }}>
                                        {{ $tmpl->name }} (v{{ $tmpl->version }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="evaluator_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                        Nama Evaluator / Petugas <span class="text-rose-500">*</span>
                    </label>
                    <input id="evaluator_name" name="evaluator_name" type="text" required
                           value="{{ old('evaluator_name', auth()->user()->name) }}"
                           placeholder="Nama lengkap evaluator"
                           class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label for="executed_at" class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                        Tanggal &amp; Waktu Pelaksanaan <span class="text-rose-500">*</span>
                    </label>
                    <input id="executed_at" name="executed_at" type="datetime-local" required
                           value="{{ old('executed_at', now()->format('Y-m-d\TH:i')) }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-4 text-xs text-blue-800">
                    <div class="font-semibold mb-1">Informasi Pengisian:</div>
                    Setelah draf dibuat, Anda akan diarahkan ke formulir pengisian kuesioner dinamis. Anda dapat menyimpan draf kapan saja sebelum mengirim laporan akhir.
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('app.surveys.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                        Batal
                    </a>
                    <button type="submit"
                            class="rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 transition">
                        Buat Draf &amp; Lanjutkan Pengisian &rarr;
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
