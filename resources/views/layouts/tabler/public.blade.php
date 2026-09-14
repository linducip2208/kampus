<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? $brand['name'] }}</title>
    @if($brand['favicon'])<link rel="icon" href="{{ Storage::url($brand['favicon']) }}">@endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root { --tblr-primary: {{ $brand['primaryColor'] }}; --campus-secondary: {{ $brand['secondaryColor'] }}; }</style>
    @stack('head')
</head>
<body class="d-flex flex-column">
    <header class="navbar navbar-expand-md navbar-light sticky-top bg-body border-bottom d-print-none">
        <div class="container-xl">
            <a class="navbar-brand navbar-brand-autodark" href="{{ route('home') }}">
                @if($brand['logo'])<img src="{{ Storage::url($brand['logo']) }}" height="32" alt="{{ $brand['name'] }}">@else<span class="avatar avatar-sm bg-primary text-white me-2">{{ str($brand['shortName'])->substr(0, 1) }}</span><span>{{ $brand['shortName'] }}</span>@endif
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#public-navbar" aria-controls="public-navbar" aria-expanded="false" aria-label="Buka navigasi"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="public-navbar">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}"><span class="nav-link-icon"><i class="ti ti-home"></i></span>Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('blog') }}"><span class="nav-link-icon"><i class="ti ti-news"></i></span>Berita</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('faq') }}"><span class="nav-link-icon"><i class="ti ti-help-circle"></i></span>FAQ</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('docs') }}"><span class="nav-link-icon"><i class="ti ti-book-2"></i></span>Dokumentasi</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('contact') }}"><span class="nav-link-icon"><i class="ti ti-mail"></i></span>Kontak</a></li>
                </ul>
                <div class="navbar-nav flex-row ms-md-3 gap-2 align-items-center"><button type="button" class="btn btn-icon" data-campus-theme-toggle aria-label="Ubah tema"><i class="ti ti-sun"></i></button><a class="btn btn-primary" href="{{ route('login') }}"><i class="ti ti-login me-2"></i>Masuk</a></div>
            </div>
        </div>
    </header>
    <main class="flex-fill">@yield('content')</main>
    <footer class="footer footer-transparent d-print-none"><div class="container-xl"><div class="row align-items-center"><div class="col text-center text-md-start">© {{ date('Y') }} {{ $brand['name'] }}</div><div class="col-auto ms-auto"><a href="{{ route('docs') }}" class="link-secondary">Dokumentasi</a></div></div></div></footer>
    @stack('scripts')
</body>
</html>
