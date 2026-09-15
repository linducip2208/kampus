@extends('layouts.tabler.admin', ['title' => 'Dashboard Administrasi'])

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Ruang kerja {{ $roleNames->join(', ') }}</div>
                <h1 class="page-title">Dashboard administrasi</h1>
            </div>
            <div class="col-auto ms-auto"><a href="{{ route('docs') }}" class="btn btn-outline-primary"><i class="ti ti-book-2 me-2"></i>Panduan operasional</a></div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-deck row-cards">
            @foreach([
                ['activeStudents','Mahasiswa aktif','ti-users','azure'],
                ['programs','Program studi','ti-school','teal'],
                ['courses','Mata kuliah','ti-book','blue'],
                ['pendingKrs','KRS menunggu','ti-checklist','yellow'],
                ['applicants','Pendaftar PMB','ti-user-plus','purple'],
                ['collected','Pembayaran diterima','ti-cash-banknote','green'],
                ['outstanding','Piutang berjalan','ti-alert-triangle','red'],
            ] as $metric)
                @if($metrics[$metric[0]] !== null)
                    <div class="col-12 col-sm-6 col-xl-3"><x-tabler.stat-card :label="$metric[1]" :value="$metrics[$metric[0]]" :icon="$metric[2]" :color="$metric[3]" /></div>
                @endif
            @endforeach
        </div>

        <div class="row row-cards mt-1">
            <div class="col-12 col-xl-8">
                <x-tabler.card title="KRS terbaru">
                    @if($recentPlans->isEmpty())
                        <x-tabler.empty-state title="Tidak ada antrean KRS" description="KRS baru yang berada dalam scope Anda akan tampil di sini." icon="ti-checklist" />
                    @else
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead><tr><th>Mahasiswa</th><th>Semester</th><th>SKS</th><th>Status</th></tr></thead>
                                <tbody>@foreach($recentPlans as $plan)<tr><td><div class="fw-semibold">{{ $plan->enrollment?->studentProfile?->full_name }}</div><div class="text-secondary small">{{ $plan->enrollment?->studentProfile?->student_number }}</div></td><td>{{ $plan->semester?->name }}</td><td>{{ $plan->total_credits }}</td><td><x-tabler.status :value="$plan->status" /></td></tr>@endforeach</tbody>
                            </table>
                        </div>
                    @endif
                </x-tabler.card>
            </div>
            <div class="col-12 col-xl-4">
                <x-tabler.card title="Kontrol operasional">
                    <div class="list-group list-group-flush">
                        @if(auth()->user()->hasPermission('krs.view'))<a href="{{ route('filament.admin.resources.study-plans.index') }}" class="list-group-item list-group-item-action d-flex align-items-center"><span class="avatar bg-yellow-lt text-yellow me-3"><i class="ti ti-checklist"></i></span><span><span class="fw-semibold d-block">Tinjau KRS</span><span class="text-secondary small">Buka antrean dan histori persetujuan</span></span><i class="ti ti-chevron-right ms-auto"></i></a>@endif
                        @if(auth()->user()->hasPermission('student_invoices.view'))<a href="{{ route('filament.admin.resources.student-invoices.index') }}" class="list-group-item list-group-item-action d-flex align-items-center"><span class="avatar bg-red-lt text-red me-3"><i class="ti ti-file-invoice"></i></span><span><span class="fw-semibold d-block">Tindak lanjuti piutang</span><span class="text-secondary small">Lihat invoice dan saldo berjalan</span></span><i class="ti ti-chevron-right ms-auto"></i></a>@endif
                        @if(auth()->user()->hasPermission('students.view'))<a href="{{ route('filament.admin.resources.student-profiles.index') }}" class="list-group-item list-group-item-action d-flex align-items-center"><span class="avatar bg-azure-lt text-azure me-3"><i class="ti ti-users"></i></span><span><span class="fw-semibold d-block">Data mahasiswa</span><span class="text-secondary small">Cari NIM dan profil akademik</span></span><i class="ti ti-chevron-right ms-auto"></i></a>@endif
                    </div>
                </x-tabler.card>
            </div>
        </div>
    </div>
</div>
@endsection
