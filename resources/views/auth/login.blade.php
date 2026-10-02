<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — {{ config('app.name', 'Mystery Passenger') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col justify-center bg-gradient-to-br from-slate-50 via-white to-blue-50 py-12 sm:px-6 lg:px-8 text-slate-900 antialiased font-sans">
    <div class="sm:mx-auto sm:w-full sm:max-w-md px-4">
        {{-- App Logo & Header --}}
        <div class="text-center">
            <a href="{{ route('landing') }}" class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/20 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-7 w-7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                </svg>
            </a>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">
                Masuk ke Akun Anda
            </h2>
            <p class="mt-1.5 text-xs text-slate-500">
                Portal Pelaporan &amp; Evaluasi Mystery Passenger
            </p>
        </div>

        {{-- Login Card --}}
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-8 shadow-xl shadow-slate-200/50">
                @if (session('status'))
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->has('login'))
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">
                        {{ $errors->first('login') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="login" class="block text-xs font-semibold text-slate-700">
                            Email atau Username
                        </label>
                        <div class="mt-1.5">
                            <input id="login" name="login" type="text" autocomplete="username" required
                                   value="{{ old('login') }}"
                                   placeholder="mis. surveyor@address.com atau budi"
                                   class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-xs font-semibold text-slate-700">
                                Kata Sandi
                            </label>
                        </div>
                        <div class="mt-1.5">
                            <input id="password" name="password" type="password" autocomplete="current-password" required
                                   placeholder="••••••••"
                                   class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-xs text-slate-600">Ingat saya</span>
                        </label>
                    </div>

                    <div>
                        <button type="submit"
                                class="flex w-full justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-500/20 transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Masuk
                        </button>
                    </div>
                </form>

                <div class="mt-6 border-t border-slate-100 pt-4 text-center">
                    <a href="{{ route('landing') }}" class="text-xs font-medium text-slate-500 hover:text-slate-700">
                        &larr; Kembali ke Halaman Utama
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
