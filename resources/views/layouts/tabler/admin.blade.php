<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Administrasi — '.$brand['name'] }}</title>
    @if($brand['favicon'])<link rel="icon" href="{{ Storage::url($brand['favicon']) }}">@endif
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>:root { --tblr-primary: {{ $brand['primaryColor'] }}; --campus-secondary: {{ $brand['secondaryColor'] }}; } .campus-nav-label{font-size:.66rem;letter-spacing:.09em}.navbar-vertical .nav-link{min-height:42px}@media(max-width:991.98px){.navbar-vertical{max-height:100vh;overflow-y:auto}}</style>
</head>
<body>
@php
    $groups = [
        'PENERIMAAN' => [
            ['label'=>'Pendaftar','icon'=>'ti-user-plus','route'=>'filament.admin.resources.applicants.index','permission'=>'applicants.view','roles'=>['pmb']],
        ],
        'AKADEMIK' => [
            ['label'=>'Tahun Akademik','icon'=>'ti-calendar-stats','route'=>'filament.admin.resources.academic-years.index','permission'=>'academic_years.view'],
            ['label'=>'Semester','icon'=>'ti-calendar-event','route'=>'filament.admin.resources.semesters.index','permission'=>'semesters.view'],
            ['label'=>'Fakultas','icon'=>'ti-building-community','route'=>'filament.admin.resources.faculties.index','permission'=>'faculties.view'],
            ['label'=>'Program Studi','icon'=>'ti-school','route'=>'filament.admin.resources.study-programs.index','permission'=>'study_programs.view'],
            ['label'=>'Kurikulum','icon'=>'ti-books','route'=>'filament.admin.resources.curricula.index','permission'=>'curricula.view'],
            ['label'=>'Mata Kuliah','icon'=>'ti-book','route'=>'filament.admin.resources.courses.index','permission'=>'courses.view'],
            ['label'=>'Penawaran','icon'=>'ti-calendar-plus','route'=>'filament.admin.resources.course-offerings.index','permission'=>'course_offerings.view'],
            ['label'=>'Kelas','icon'=>'ti-door','route'=>'filament.admin.resources.class-sections.index','permission'=>'class_sections.view'],
            ['label'=>'Jadwal','icon'=>'ti-calendar-time','route'=>'admin.schedules.index','permission'=>'class_sections.view'],
        ],
        'MAHASISWA & PERKULIAHAN' => [
            ['label'=>'Mahasiswa','icon'=>'ti-users','route'=>'filament.admin.resources.student-profiles.index','permission'=>'students.view'],
            ['label'=>'Enrollment','icon'=>'ti-id-badge-2','route'=>'filament.admin.resources.student-enrollments.index','permission'=>'student_enrollments.view'],
            ['label'=>'KRS','icon'=>'ti-checklist','route'=>'filament.admin.resources.study-plans.index','permission'=>'krs.view'],
            ['label'=>'Assignment','icon'=>'ti-clipboard-text','route'=>'filament.admin.resources.assignments.index','permission'=>'assignments.view','roles'=>['super_admin']],
        ],
        'KEUANGAN' => [
            ['label'=>'Invoice','icon'=>'ti-file-invoice','route'=>'filament.admin.resources.student-invoices.index','permission'=>'student_invoices.view'],
            ['label'=>'Pembayaran','icon'=>'ti-credit-card-pay','route'=>'filament.admin.resources.payments.index','permission'=>'payments.view'],
        ],
        'SISTEM' => [
            ['label'=>'Audit Log','icon'=>'ti-shield-search','route'=>'filament.admin.resources.audit-logs.index','permission'=>'audit_logs.view'],
            ['label'=>'Blog','icon'=>'ti-news','route'=>'filament.admin.resources.blog-posts.index','permission'=>'blog_posts.view','roles'=>['super_admin']],
        ],
    ];
@endphp
<div class="page">
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#campus-admin-menu" aria-controls="campus-admin-menu" aria-expanded="false" aria-label="Buka navigasi"><span class="navbar-toggler-icon"></span></button>
            <h1 class="navbar-brand navbar-brand-autodark"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-white d-flex align-items-center gap-2"><span class="avatar avatar-sm bg-primary text-white">{{ str($brand['shortName'])->substr(0,1) }}</span><span>{{ $brand['shortName'] }}</span></a></h1>
            <div class="collapse navbar-collapse" id="campus-admin-menu">
                <ul class="navbar-nav pt-lg-3">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><span class="nav-link-icon"><i class="ti ti-layout-dashboard"></i></span><span class="nav-link-title">Dashboard</span></a></li>
                    @foreach($groups as $group => $items)
                        @php($visibleItems = collect($items)->filter(fn($item) => Route::has($item['route']) && (auth()->user()->hasPermission($item['permission']) || auth()->user()->hasRole($item['roles'] ?? []))))
                        @if($visibleItems->isNotEmpty())
                            <li class="nav-item mt-3 px-3 text-secondary fw-bold campus-nav-label">{{ $group }}</li>
                            @foreach($visibleItems as $item)
                                <li class="nav-item"><a class="nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}"><span class="nav-link-icon"><i class="ti {{ $item['icon'] }}"></i></span><span class="nav-link-title">{{ $item['label'] }}</span></a></li>
                            @endforeach
                        @endif
                    @endforeach
                </ul>
                <div class="mt-auto py-3"><a href="{{ route('docs') }}" class="nav-link"><span class="nav-link-icon"><i class="ti ti-book-2"></i></span><span class="nav-link-title">Dokumentasi</span></a></div>
            </div>
        </div>
    </aside>
    <div class="page-wrapper">
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">
                <div class="navbar-nav flex-row order-md-last ms-auto align-items-center gap-2">
                    <button type="button" class="btn btn-icon" data-campus-theme-toggle aria-label="Ubah tema"><i class="ti ti-sun"></i></button>
                    <div class="nav-item dropdown"><button type="button" class="nav-link btn btn-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Menu pengguna"><span class="avatar avatar-sm bg-primary-lt">{{ str(auth()->user()->name)->substr(0,1) }}</span><span class="d-none d-xl-block ps-2 text-start"><span class="d-block">{{ auth()->user()->name }}</span><span class="d-block mt-1 small text-secondary">{{ auth()->user()->roles()->value('label') }}</span></span></button><div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow"><a class="dropdown-item" href="{{ route('home') }}"><i class="ti ti-home me-2"></i>Halaman depan</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="dropdown-item text-danger"><i class="ti ti-logout me-2"></i>Keluar</button></form></div></div>
                </div>
            </div>
        </header>
        @yield('content')
        <footer class="footer footer-transparent d-print-none"><div class="container-xl"><div class="text-secondary">© {{ date('Y') }} {{ $brand['name'] }}</div></div></footer>
    </div>
</div>
</body>
</html>