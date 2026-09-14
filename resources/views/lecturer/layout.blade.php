<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Portal Dosen — '.config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700|inter:400,500,600,700,800" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root { --ink: #0b1220; --accent: #16c6d4; --canvas: #f5f7fb; }
        * { -webkit-font-smoothing: antialiased; }
        body { font-family: Inter, sans-serif; }
        .font-display { font-family: 'IBM Plex Sans', sans-serif; }
        .card-lift { transition: transform .2s ease-out, box-shadow .2s ease-out; }
        .card-lift:hover { transform: translateY(-4px); box-shadow: 0 18px 36px -22px #0b1220; }
        .focus-ring:focus-visible { outline: 3px solid rgba(22, 198, 212, .45); outline-offset: 3px; }
        .reveal { animation: rise .55s ease-out both; }
        @keyframes rise { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; } }
    </style>
</head>
<body class="min-h-screen bg-[var(--canvas)] text-slate-900" x-data="{ sidebarOpen: false }">
    <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-slate-950/50 md:hidden" @click="sidebarOpen = false"></div>
    <div class="min-h-screen md:flex">
        <aside class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full overflow-y-auto bg-[var(--ink)] p-5 text-white transition-transform md:static md:w-64 md:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : ''" aria-label="Navigasi portal dosen">
            <div class="flex items-center justify-between">
                <a href="{{ route('lecturer.dashboard') }}" class="focus-ring flex items-center gap-3 rounded-xl">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-[var(--accent)] font-bold text-[var(--ink)]">C</span>
                    <span class="font-display text-lg font-bold">{{ config('app.name') }}</span>
                </a>
                <button type="button" class="focus-ring grid h-11 w-11 place-items-center rounded-xl text-slate-400 hover:bg-white/10 md:hidden" aria-label="Tutup navigasi" @click="sidebarOpen = false">×</button>
            </div>
            <div class="mt-10 rounded-2xl border border-white/10 bg-white/[.06] p-4">
                <p class="text-[10px] font-bold uppercase tracking-[.18em] text-cyan-300">Portal dosen</p>
                <p class="mt-2 truncate font-semibold">{{ $lecturer->employee?->full_name ?? auth()->user()->name }}</p>
                <p class="mt-1 truncate text-xs text-slate-400">{{ $lecturer->nidn ?: auth()->user()->email }}</p>
            </div>
            <p class="mt-8 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-slate-500">Ruang kerja</p>
            <nav class="mt-3 space-y-1">
                @foreach([
                    ['lecturer.dashboard', 'Dasbor', 'grid'],
                    ['lecturer.schedule', 'Jadwal mengajar', 'calendar'],
                    ['lecturer.classes', 'Kelas saya', 'book'],
                    ['lecturer.advisees', 'Mahasiswa bimbingan', 'users'],
                ] as $nav)
                    <a href="{{ route($nav[0]) }}" class="focus-ring flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm transition hover:translate-x-0.5 hover:bg-white/10 {{ request()->routeIs($nav[0]) ? 'bg-cyan-400 font-semibold text-[var(--ink)]' : 'text-slate-300' }}">
                        @if ($nav[2] === 'grid')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/></svg>
                        @elseif ($nav[2] === 'calendar')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
                        @elseif ($nav[2] === 'book')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21V5.5Z"/><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M8 7h8M8 11h6"/></svg>
                        @else
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20a6 6 0 0 1 12 0M14 19a5 5 0 0 1 7 0"/></svg>
                        @endif
                        <span>{{ $nav[1] }}</span>
                    </a>
                @endforeach
            </nav>
            <div class="mt-8 border-t border-white/10 pt-5">
                <a href="{{ route('home') }}" class="focus-ring flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm text-slate-400 hover:bg-white/10 hover:text-white"><span class="text-lg" aria-hidden="true">↗</span>Halaman depan</a>
                <form class="mt-1" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="focus-ring flex min-h-11 w-full items-center gap-3 rounded-xl px-3 text-left text-sm text-slate-400 hover:bg-rose-500/10 hover:text-rose-300"><span class="text-lg" aria-hidden="true">⇥</span>Keluar</button>
                </form>
            </div>
        </aside>
        <main class="min-w-0 flex-1">
            <header class="sticky top-0 z-20 flex items-center justify-between border-b border-slate-200/80 bg-white/90 px-4 py-4 backdrop-blur md:px-8">
                <div class="flex items-center gap-3">
                    <button type="button" class="focus-ring grid h-11 w-11 place-items-center rounded-xl border border-slate-200 text-slate-700 md:hidden" aria-label="Buka navigasi" @click="sidebarOpen = true"><span class="text-xl">☰</span></button>
                    <div><p class="text-xs font-medium text-slate-500">{{ now()->translatedFormat('l, d F Y') }}</p><h1 class="font-display text-xl font-bold">{{ $heading ?? 'Ruang kerja dosen' }}</h1></div>
                </div>
                <div class="hidden items-center gap-3 sm:flex"><span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">● Sesi aktif</span><span class="max-w-40 truncate text-sm font-semibold text-slate-700">{{ auth()->user()->name }}</span></div>
            </header>
            <div class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">@yield('content')</div>
        </main>
    </div>
</body>
</html>
