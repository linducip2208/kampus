<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Portal — Campus ERP' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700|inter:400,500,600,700,800" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: Inter, sans-serif; }
        .font-display { font-family: 'IBM Plex Sans', sans-serif; }
        .card-lift { transition: transform .2s, box-shadow .2s; }
        .card-lift:hover { transform: translateY(-4px); box-shadow: 0 16px 32px -16px #0b1220; }
        .reveal { animation: fadeSlide .65s both; }
        @keyframes fadeSlide { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
        @media (prefers-reduced-motion: reduce) { * { animation-duration: .01ms !important; transition-duration: .01ms !important; } }
    </style>
</head>
<body class="min-h-screen bg-[#f5f7fb] text-slate-900">
<div class="min-h-screen md:flex">
    <aside class="hidden w-64 shrink-0 bg-[#0b1220] p-5 text-white md:block">
        <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-cyan-400 font-bold text-[#0b1220]">C</span>
            <span class="font-display text-lg font-bold">Campus ERP</span>
        </a>
        <p class="mt-10 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-slate-500">Portal mahasiswa</p>
        <nav class="mt-4 space-y-1">
            @foreach([
                ['portal.dashboard', 'Dashboard', '⌂'],
                ['portal.krs', 'KRS', '▦'],
                ['portal.academic-record', 'KHS & Transkrip', '▤'],
                ['portal.invoices', 'Pembayaran', '◉'],
            ] as $nav)
                <a class="flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm {{ request()->routeIs($nav[0]) ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-white/10' }}" href="{{ route($nav[0]) }}">
                    <span class="text-lg">{{ $nav[2] }}</span>{{ $nav[1] }}
                </a>
            @endforeach
        </nav>
        <div class="mt-10 border-t border-white/10 pt-5">
            <p class="px-3 text-xs font-semibold text-slate-400">{{ auth()->user()->name }}</p>
            <p class="px-3 text-xs text-slate-600">{{ auth()->user()->email }}</p>
            <form class="mt-4" method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full rounded-xl px-3 py-2 text-left text-sm text-slate-400 hover:bg-rose-500/10 hover:text-rose-300">Keluar dari portal</button>
            </form>
        </div>
    </aside>
    <main class="min-w-0 flex-1">
        <header class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4 md:px-8">
            <div>
                <p class="text-xs font-semibold text-slate-500">{{ now()->translatedFormat('l, d F Y') }}</p>
                <h1 class="font-display text-xl font-bold">{{ $heading ?? 'Ruang belajar Anda' }}</h1>
            </div>
            <a class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 md:hidden" href="{{ route('home') }}">Beranda</a>
        </header>
        <div class="mx-auto max-w-6xl p-5 md:p-8">@yield('content')</div>
    </main>
</div>
</body>
</html>
