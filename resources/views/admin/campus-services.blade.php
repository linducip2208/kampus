@extends('layouts.tabler.admin', ['title' => 'Layanan Kampus'])

@section('content')
<div class="page-header d-print-none"><div class="container-xl"><div class="row align-items-center"><div class="col"><div class="page-pretitle">Kemahasiswaan</div><h1 class="page-title">Perpustakaan, riset, organisasi, MBKM</h1></div></div></div></div>
<div class="page-body"><div class="container-xl">
@if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3"><x-tabler.stat-card label="Judul buku" :value="$books->total()" icon="ti-books" color="azure" /></div>
    <div class="col-sm-6 col-lg-3"><x-tabler.stat-card label="Riset" :value="$research->total()" icon="ti-flask" color="green" /></div>
    <div class="col-sm-6 col-lg-3"><x-tabler.stat-card label="Organisasi" :value="$organizations->total()" icon="ti-users" color="yellow" /></div>
    <div class="col-sm-6 col-lg-3"><x-tabler.stat-card label="Program MBKM" :value="$mbkmPrograms->total()" icon="ti-briefcase" color="purple" /></div>
</div>

<div class="card mb-4">
    <div class="card-header"><h3 class="card-title"><i class="ti ti-briefcase me-2"></i>MBKM menunggu keputusan</h3></div>
    <div class="table-responsive"><table class="table table-vcenter card-table">
        <thead><tr><th>Mahasiswa</th><th>Program</th><th class="w-1">Tindakan</th></tr></thead>
        <tbody>
        @forelse($pendingMbkm as $item)
            <tr>
                <td>{{ $item->enrollment?->studentProfile?->full_name }}</td>
                <td>{{ $item->program?->name }}</td>
                <td><form method="POST" action="{{ route('admin.campus-services.mbkm.decide', $item) }}" class="d-flex gap-2">@csrf<input type="hidden" name="status" value="approved"><input type="number" name="credits" value="20" min="0" max="40" class="form-control form-control-sm" style="width: 5rem"><button class="btn btn-success btn-sm">Setujui</button></form></td>
            </tr>
        @empty
            <tr><td colspan="3"><x-tabler.empty-state title="Tidak ada antrean MBKM" description="Pendaftaran yang menunggu tampil di sini." icon="ti-check" /></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="ti ti-calendar-event me-2"></i>Kegiatan menunggu persetujuan</h3></div>
    <div class="table-responsive"><table class="table table-vcenter card-table">
        <thead><tr><th>Kegiatan</th><th>Organisasi</th><th class="w-1">Tindakan</th></tr></thead>
        <tbody>
        @forelse($pendingActivities as $activity)
            <tr>
                <td>{{ $activity->title }}</td>
                <td>{{ $activity->organization?->name }}</td>
                <td><form method="POST" action="{{ route('admin.campus-services.activities.decide', $activity) }}">@csrf<input type="hidden" name="status" value="approved"><button class="btn btn-success btn-sm">Setujui</button></form></td>
            </tr>
        @empty
            <tr><td colspan="3"><x-tabler.empty-state title="Tidak ada antrean kegiatan" description="Pengajuan kegiatan tampil di sini." icon="ti-check" /></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
</div></div>
@endsection
