<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Masuk — '.$brand['name'] }}</title>
    @if($brand['favicon'])<link rel="icon" href="{{ Storage::url($brand['favicon']) }}">@endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root { --tblr-primary: {{ $brand['primaryColor'] }}; --campus-secondary: {{ $brand['secondaryColor'] }}; } .campus-auth-hero { background: linear-gradient(145deg, color-mix(in srgb, var(--tblr-primary) 72%, #071426), #071426 72%); }</style>
</head>
<body class="d-flex flex-column bg-body-tertiary">
<main class="row g-0 min-vh-100">
    <section class="col-lg-6 d-none d-lg-flex campus-auth-hero text-white p-5 flex-column justify-content-between" aria-label="Tentang portal">
        <a href="{{ route('home') }}" class="text-white text-decoration-none d-flex align-items-center gap-3"><span class="avatar bg-white text-primary">{{ str($brand['shortName'])->substr(0, 1) }}</span><span class="h2 m-0">{{ $brand['shortName'] }}</span></a>
        <div class="mw-100" style="max-width: 36rem"><span class="badge bg-azure-lt text-azure mb-4">Sistem operasi universitas</span><h1 class="display-5 fw-bold">Satu ruang kerja untuk perjalanan akademik yang utuh.</h1><p class="lead text-white-50 mt-4">Akses akademik, keuangan, pembelajaran, persetujuan, dan laporan sesuai peran Anda.</p><div class="row g-3 mt-4"><div class="col-4"><div class="border border-white border-opacity-10 rounded p-3"><i class="ti ti-school fs-1 text-azure"></i><div class="fw-semibold mt-3">Akademik</div></div></div><div class="col-4"><div class="border border-white border-opacity-10 rounded p-3"><i class="ti ti-shield-check fs-1 text-teal"></i><div class="fw-semibold mt-3">Terotorisasi</div></div></div><div class="col-4"><div class="border border-white border-opacity-10 rounded p-3"><i class="ti ti-history fs-1 text-yellow"></i><div class="fw-semibold mt-3">Teraudit</div></div></div></div></div>
        <div class="text-white-50 small">© {{ date('Y') }} {{ $brand['name'] }}</div>
    </section>
    <section class="col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5"><div class="w-100" style="max-width: 28rem">@yield('content')</div></section>
</main>
</body>
</html>
