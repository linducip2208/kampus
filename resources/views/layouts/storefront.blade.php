<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Campus ERP — Universitas Cakrawala Nusantara' }}</title>
    <meta name="description" content="Campus ERP untuk mengelola siklus akademik, mahasiswa, dosen, keuangan, dan pelaporan universitas dalam satu kendali.">
    <meta property="og:title" content="{{ $title ?? 'Campus ERP — Satu kendali untuk seluruh siklus kampus' }}">
    <meta property="og:description" content="Akademik, keuangan mahasiswa, portal mandiri, dan audit dalam satu platform universitas.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700|inter:400,500,600,700,800" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @stack('head')
</head>
<body class="bg-[#f5f7fb] text-slate-900 antialiased">
    <header class="sticky top-0 z-40 border-b border-white/10 bg-[#0b1220]/95 text-white backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="Campus ERP beranda">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-cyan-400 font-bold text-[#0b1220]">C</span>
                <span><span class="block font-display text-lg font-bold">Campus ERP</span><span class="block text-[10px] uppercase tracking-[.22em] text-slate-400">SIAKAD universitas</span></span>
            </a>
            <nav class="hidden items-center gap-7 text-sm text-slate-300 md:flex" aria-label="Navigasi utama">
                <a class="transition hover:text-white" href="{{ route('docs') }}">Dokumentasi</a>
                <a class="transition hover:text-white" href="{{ route('blog') }}">Blog</a>
                <a class="transition hover:text-white" href="{{ route('reports.index') }}">Preview laporan</a>
                <a class="rounded-lg border border-white/15 px-4 py-2 font-semibold text-white transition hover:border-cyan-300 hover:text-cyan-200" href="{{ route('login') }}">Masuk</a>
            </nav>
            <a href="{{ route('login') }}" class="rounded-lg bg-cyan-400 px-4 py-2 text-sm font-bold text-[#0b1220] md:hidden">Masuk</a>
        </div>
    </header>
    <main>@yield('content')</main>
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-8 text-sm text-slate-500 md:flex-row md:items-center md:justify-between lg:px-8">
            <p>© {{ date('Y') }} Campus ERP · Data kampus yang siap dipertanggungjawabkan.</p>
            <div class="flex gap-5"><a href="{{ route('docs') }}" class="hover:text-slate-900">Docs</a><a href="{{ route('login') }}" class="hover:text-slate-900">Portal</a><a href="mailto:hello@kampus.test" class="hover:text-slate-900">Kontak</a></div>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
