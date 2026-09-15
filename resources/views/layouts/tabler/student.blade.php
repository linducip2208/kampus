<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? $brand['name'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (() => {
            const preference = localStorage.getItem('campus-theme') || 'system';
            const theme = preference === 'system' ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : preference;
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    <style>
        .campus-sidebar { width: 17rem; }
        .campus-main { min-width: 0; }
        .campus-nav .nav-link { border-radius: .375rem; margin: .125rem .75rem; }
        .campus-nav .nav-link.active { background: var(--tblr-primary-lt); color: var(--tblr-primary); font-weight: 600; }
        .campus-nav .nav-link:not(.active):hover { background: var(--tblr-bg-surface-secondary); }
        @media (max-width: 991.98px) {
            .campus-sidebar { width: 18rem; position: fixed; inset: 0 auto 0 0; z-index: 1045; transform: translateX(-100%); transition: transform .2s ease; }
            .campus-sidebar.is-open { transform: translateX(0); }
            .campus-sidebar-backdrop { display: none; position: fixed; inset: 0; z-index: 1040; background: rgba(0,0,0,.42); }
            .campus-sidebar-backdrop.is-open { display: block; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; } }
    </style>
</head>
<body>
<div class="page">
    <div id="campus-sidebar-backdrop" class="campus-sidebar-backdrop" aria-hidden="true"></div>
    <aside id="campus-sidebar" class="navbar navbar-vertical navbar-expand-lg campus-sidebar" aria-label="Navigasi mahasiswa">
        <div class="container-fluid">
            <h1 class="navbar-brand navbar-brand-autodark">
                <a href="{{ route('portal.dashboard') }}" class="text-decoration-none d-flex align-items-center gap-2">
                    <span class="avatar avatar-sm bg-primary text-white">C</span>
                    <span>{{ $brand['shortName'] }}</span>
                </a>
            </h1>
            <div class="navbar-nav flex-row d-lg-none ms-auto">
                <button type="button" class="btn btn-icon" id="campus-sidebar-close" aria-label="Tutup navigasi"><i class="ti ti-x"></i></button>
            </div>
            <div class="collapse navbar-collapse show" id="sidebar-menu">
                <div class="mt-3 mb-2 px-3 text-secondary text-uppercase small fw-bold">Portal mahasiswa</div>
                <ul class="navbar-nav campus-nav">
                    @foreach([
                        ['portal.dashboard', 'Dashboard', 'ti-dashboard'],
                        ['portal.krs', 'KRS', 'ti-books'],
                        ['portal.attendance.index', 'Presensi', 'ti-calendar-check'],
                        ['portal.learning.index', 'Pembelajaran', 'ti-school'],
                        ['portal.lifecycle.index', 'Cuti & Status', 'ti-user-cog'],
                        ['portal.academic-record', 'KHS & Transkrip', 'ti-report-analytics'],
                        ['portal.invoices', 'Pembayaran', 'ti-credit-card'],
                    ] as $nav)
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs($nav[0]) ? 'active' : '' }}" href="{{ route($nav[0]) }}"><span class="nav-link-icon"><i class="ti {{ $nav[2] }}"></i></span><span class="nav-link-title">{{ $nav[1] }}</span></a></li>
                    @endforeach
                </ul>
                <div class="hr-text mt-4">Akun</div>
                <div class="px-3 small text-secondary">
                    <div class="fw-semibold text-body">{{ auth()->user()->name }}</div>
                    <div class="text-truncate">{{ auth()->user()->email }}</div>
                </div>
                <div class="mt-auto pt-4">
                    <a class="nav-link" href="{{ route('home') }}"><span class="nav-link-icon"><i class="ti ti-home"></i></span>Beranda</a>
                    <form method="POST" action="{{ route('logout') }}" class="mt-1">@csrf<button type="submit" class="nav-link btn btn-link text-danger w-100 text-start"><span class="nav-link-icon"><i class="ti ti-logout"></i></span>Keluar</button></form>
                </div>
            </div>
        </div>
    </aside>
    <div class="page-wrapper campus-main">
        <header class="navbar navbar-expand-md d-print-none sticky-top border-bottom bg-body">
            <div class="container-xl">
                <button type="button" class="navbar-toggler d-lg-none me-2" id="campus-sidebar-open" aria-label="Buka navigasi"><span class="navbar-toggler-icon"></span></button>
                <div class="navbar-nav flex-row order-md-last ms-auto align-items-center gap-2">
                    <button type="button" class="btn btn-icon" data-campus-theme-toggle aria-label="Ubah tema"><i class="ti ti-sun"></i></button>
                    <div class="nav-item dropdown"><button type="button" class="nav-link btn btn-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Menu profil"><span class="avatar avatar-sm bg-primary-lt">{{ str($enrollment->studentProfile->full_name ?? auth()->user()->name)->substr(0, 1) }}</span><span class="d-none d-xl-block ps-2 text-start"><span class="d-block">{{ auth()->user()->name }}</span><span class="d-block mt-1 small text-secondary">Mahasiswa</span></span></button><div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow"><a class="dropdown-item" href="{{ route('home') }}"><i class="ti ti-home me-2"></i>Halaman depan</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item text-danger" type="submit"><i class="ti ti-logout me-2"></i>Keluar</button></form></div></div>
                </div>
                <div><div class="text-secondary small">{{ now()->translatedFormat('l, d F Y') }}</div><div class="fw-semibold">{{ $heading ?? 'Portal mahasiswa' }}</div></div>
            </div>
        </header>
        <div class="page-header d-print-none"><div class="container-xl"><div class="row align-items-center"><div class="col"><h2 class="page-title">{{ $heading ?? 'Portal mahasiswa' }}</h2></div></div></div></div>
        <div class="page-body"><div class="container-xl">@yield('content')</div></div>
    </div>
</div>
<script>
    (() => {
        const sidebar = document.getElementById('campus-sidebar');
        const backdrop = document.getElementById('campus-sidebar-backdrop');
        const setOpen = (open) => { sidebar.classList.toggle('is-open', open); backdrop.classList.toggle('is-open', open); };
        document.getElementById('campus-sidebar-open')?.addEventListener('click', () => setOpen(true));
        document.getElementById('campus-sidebar-close')?.addEventListener('click', () => setOpen(false));
        backdrop?.addEventListener('click', () => setOpen(false));
    })();
</script>
</body>
</html>


