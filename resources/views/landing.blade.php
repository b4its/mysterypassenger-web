<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Mystery Passenger') }} — Selamat Datang</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 text-slate-900 antialiased">
    <div class="mx-auto flex min-h-screen max-w-5xl flex-col px-6 py-10">
        {{-- Header --}}
        <header class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                    </svg>
                </span>
                <div>
                    <p class="text-lg font-bold tracking-tight">{{ config('app.name', 'Mystery Passenger') }}</p>
                    <p class="text-xs text-slate-500">Sistem Survei Mystery Passenger</p>
                </div>
            </div>

            @if ($isAuthenticated)
                <div class="flex items-center gap-3 text-sm">
                    <span class="hidden text-slate-500 sm:inline">Masuk sebagai <span class="font-medium text-slate-700">{{ $user->name }}</span></span>
                    <a href="{{ $user->isAdmin() ? url('/admin') : route('app.dashboard') }}"
                       class="rounded-lg border border-slate-200 bg-white px-4 py-2 font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:text-slate-900">
                        Buka Aplikasi
                    </a>
                </div>
            @else
                <a href="{{ route('login') }}"
                   class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    Masuk
                </a>
            @endif
        </header>

        {{-- Hero --}}
        <main class="flex flex-1 flex-col justify-center py-12">
            <div class="mb-12 max-w-2xl">
                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-blue-700">
                    Selamat Datang
                </span>
                <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">
                    Pantau kualitas layanan transportasi,
                    <span class="text-blue-600">satu perjalanan sekaligus.</span>
                </h1>
                <p class="mt-4 text-base leading-relaxed text-slate-600">
                    Program <strong>Mystery Passenger</strong> membantu evaluator menumpang moda
                    transportasi secara anonim, mengisi lembar ceklist &amp; laporan kegiatan,
                    lalu mengolahnya menjadi skor dan dokumen resmi.
                </p>
                <p class="mt-3 text-sm text-slate-500">
                    Silakan pilih salah satu langkah di bawah untuk memulai.
                </p>
            </div>

            {{-- Three primary actions --}}
            <div class="grid gap-6 sm:grid-cols-3">
                @foreach ($actions as $action)
                    <a href="{{ $action['url'] }}"
                       class="group flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md">
                        <div class="flex items-center justify-between mb-4">
                            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition group-hover:bg-blue-600 group-hover:text-white">
                                @include('partials.landing-icon', ['icon' => $action['icon']])
                            </span>
                            @if($action['adminOnly'] ?? false)
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                                    Khusus Admin
                                </span>
                            @endif
                        </div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ $action['title'] }}</h2>
                        <p class="mt-2 flex-1 text-sm leading-relaxed text-slate-600">{{ $action['description'] }}</p>
                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-blue-600">
                            Mulai
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4 transition group-hover:translate-x-0.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </span>
                    </a>
                @endforeach
            </div>

            @guest
                <p class="mt-8 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Anda belum masuk. Aksi di atas memerlukan akun —
                    <a href="{{ route('login') }}" class="font-semibold underline">masuk di sini</a>
                    atau hubungi administrator untuk mendapatkan akses.
                </p>
            @endguest
        </main>

        {{-- Footer --}}
        <footer class="border-t border-slate-200 pt-6 text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} {{ config('app.name', 'Mystery Passenger') }} — Multi Moda Transportasi
        </footer>
    </div>
</body>
</html>
