<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? $brand['name'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>const p=localStorage.getItem('campus-theme')||'system';document.documentElement.setAttribute('data-bs-theme',p==='system'?(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):p);</script>
    <style>
        .campus-sidebar { width: 17rem; }
        .campus-main { min-width: 0; }
        .campus-nav .nav-link { border-radius: .375rem; margin: .125rem .75rem; }
        .campus-nav .nav-link.active { background: var(--tblr-primary-lt); color: var(--tblr-primary); font-weight: 600; }
        .campus-nav .nav-link:not(.active):hover { background: var(--tblr-bg-surface-secondary); }
        @media (max-width: 991.98px) { .campus-sidebar { width: 18rem; position: fixed; inset: 0 auto 0 0; z-index: 1045; transform: translateX(-100%); transition: transform .2s ease; } .campus-sidebar.is-open { transform: translateX(0); } .campus-sidebar-backdrop { display: none; position: fixed; inset: 0; z-index: 1040; background: rgba(0,0,0,.42); } .campus-sidebar-backdrop.is-open { display: block; } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; } }
    </style>
</head>
<body>
<div class="page">
    <div id="campus-sidebar-backdrop" class="campus-sidebar-backdrop" aria-hidden="true"></div>
    <aside id="campus-sidebar" class="navbar navbar-vertical navbar-expand-lg campus-sidebar" aria-label="Navigasi dosen">
        <div class="container-fluid">
            <h1 class="navbar-brand navbar-brand-autodark"><a href="{{ route('lecturer.dashboard') }}" class="text-decoration-none d-flex align-items-center gap-2"><span class="avatar avatar-sm bg-primary text-white">C</span><span>{{ $brand['shortName'] }}</span></a></h1>
            <div class="navbar-nav flex-row d-lg-none ms-auto"><button type="button" class="btn btn-icon" id="campus-sidebar-close" aria-label="Tutup navigasi"><i class="ti ti-x"></i></button></div>
            <div class="collapse navbar-collapse show" id="sidebar-menu">
                <div class="card bg-primary-lt border-0 mb-3"><div class="card-body py-3"><div class="text-primary text-uppercase small fw-bold">Portal dosen</div><div class="fw-semibold text-truncate mt-1">{{ $lecturer->employee?->full_name ?? auth()->user()->name }}</div><div class="small text-secondary text-truncate">{{ $lecturer->nidn ?: auth()->user()->email }}</div></div></div>
                <div class="mb-2 px-3 text-secondary text-uppercase small fw-bold">Ruang kerja</div>
                <ul class="navbar-nav campus-nav">
                    @foreach([['lecturer.dashboard','Dashboard','ti-dashboard'],['lecturer.schedule','Jadwal Mengajar','ti-calendar-event'],['lecturer.classes','Kelas Saya','ti-book'],['lecturer.attendance.index','Presensi','ti-calendar-check'],['lecturer.learning.index','Pembelajaran','ti-school'],['lecturer.grades.index','Input Nilai','ti-calculator'],['lecturer.advisees','Mahasiswa Bimbingan','ti-users-group']] as $nav)
                        <li class="nav-item"><a href="{{ route($nav[0]) }}" class="nav-link {{ request()->routeIs($nav[0]) ? 'active' : '' }}"><span class="nav-link-icon"><i class="ti {{ $nav[2] }}"></i></span><span class="nav-link-title">{{ $nav[1] }}</span></a></li>
                    @endforeach
                </ul>
                <div class="mt-auto pt-4"><a href="{{ route('home') }}" class="nav-link"><span class="nav-link-icon"><i class="ti ti-home"></i></span>Halaman depan</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="nav-link btn btn-link text-danger w-100 text-start"><span class="nav-link-icon"><i class="ti ti-logout"></i></span>Keluar</button></form></div>
            </div>
        </div>
    </aside>
    <div class="page-wrapper campus-main">
        <header class="navbar navbar-expand-md d-print-none sticky-top border-bottom bg-body"><div class="container-xl"><button type="button" class="navbar-toggler d-lg-none me-2" id="campus-sidebar-open" aria-label="Buka navigasi"><span class="navbar-toggler-icon"></span></button><div class="navbar-nav flex-row order-md-last ms-auto align-items-center gap-2"><button type="button" class="btn btn-icon" data-campus-theme-toggle aria-label="Ubah tema"><i class="ti ti-sun"></i></button><span class="badge bg-green-lt text-green">Sesi aktif</span><span class="d-none d-sm-inline fw-semibold">{{ auth()->user()->name }}</span></div><div><div class="text-secondary small">{{ now()->translatedFormat('l, d F Y') }}</div><div class="fw-semibold">{{ $heading ?? 'Ruang kerja dosen' }}</div></div></div></header>
        <div class="page-header d-print-none"><div class="container-xl"><h2 class="page-title">{{ $heading ?? 'Ruang kerja dosen' }}</h2></div></div>
        <div class="page-body"><div class="container-xl">@yield('content')</div></div>
    </div>
</div>
<script>(() => { const sidebar=document.getElementById('campus-sidebar'), backdrop=document.getElementById('campus-sidebar-backdrop'); const setOpen=(open)=>{sidebar.classList.toggle('is-open',open);backdrop.classList.toggle('is-open',open)}; document.getElementById('campus-sidebar-open')?.addEventListener('click',()=>setOpen(true)); document.getElementById('campus-sidebar-close')?.addEventListener('click',()=>setOpen(false)); backdrop?.addEventListener('click',()=>setOpen(false)); })();</script>
</body>
</html>
