@extends('layouts.tabler.admin', ['title' => 'HRM & BKD'])

@section('content')
<div class="page-header d-print-none"><div class="container-xl"><div class="row align-items-center"><div class="col"><div class="page-pretitle">Kepegawaian</div><h1 class="page-title">Unit, jabatan, cuti, BKD</h1></div></div></div></div>
<div class="page-body"><div class="container-xl">
@if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <form method="POST" action="{{ route('admin.hrm.units.store') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">Unit baru</h3></div><div class="card-body"><div class="row g-2"><div class="col-7"><label class="form-label">Nama</label><input name="name" class="form-control" required maxlength="255"></div><div class="col-5"><label class="form-label">Kode</label><input name="code" class="form-control" required maxlength="50"></div></div></div><div class="card-footer"><button class="btn btn-primary w-100">Simpan unit</button></div>
        <div class="card-body border-top">@forelse($units as $unit)<div class="d-flex justify-content-between py-1"><span>{{ $unit->name }} <span class="text-secondary">({{ $unit->code }})</span></span><span class="badge bg-azure-lt">{{ $unit->positions_count }}</span></div>@empty<div class="text-secondary small">Belum ada unit.</div>@endforelse</div></form>
    </div>
    <div class="col-lg-6">
        <form method="POST" action="{{ route('admin.hrm.positions.store') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">Jabatan baru</h3></div><div class="card-body"><div class="row g-2"><div class="col-7"><label class="form-label">Nama</label><input name="name" class="form-control" required maxlength="255"></div><div class="col-5"><label class="form-label">Kode</label><input name="code" class="form-control" required maxlength="50"></div></div><label class="form-label mt-2">Unit</label><select name="unit_id" class="form-select"><option value="">— Tanpa unit —</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select></div><div class="card-footer"><button class="btn btn-primary w-100">Simpan jabatan</button></div></form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h3 class="card-title"><i class="ti ti-calendar-pause me-2 text-yellow"></i>Cuti pegawai menunggu</h3></div>
    <div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Pegawai</th><th>Periode</th><th>Alasan</th><th class="w-1">Tindakan</th></tr></thead><tbody>@forelse($leaves as $leave)<tr><td>{{ $leave->employee?->full_name ?? $leave->employee?->employee_number }}</td><td>{{ $leave->starts_on->toDateString() }} &ndash; {{ $leave->ends_on->toDateString() }}</td><td>{{ str($leave->reason)->limit(100) }}</td><td><form method="POST" action="{{ route('admin.hrm.leaves.decide', $leave) }}" class="d-flex gap-2">@csrf<select name="status" class="form-select form-select-sm"><option value="approved">Setujui</option><option value="rejected">Tolak</option></select><button class="btn btn-success btn-sm">Proses</button></form></td></tr>@empty<tr><td colspan="4"><x-tabler.empty-state title="Tidak ada antrean cuti" description="Pengajuan cuti pegawai tampil di sini." icon="ti-check" /></td></tr>@endforelse</tbody></table></div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="ti ti-briefcase me-2 text-azure"></i>BKD menunggu persetujuan</h3></div>
    <div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Dosen</th><th>Semester</th><th>Total SKS</th><th class="w-1">Tindakan</th></tr></thead><tbody>@forelse($workloads as $row)<tr><td>{{ $row['workload']->lecturer?->employee?->full_name }}</td><td>{{ $row['workload']->semester?->name }}</td><td>{{ $row['summary']['total_sks'] }} / {{ $row['summary']['target_sks'] }}</td><td><form method="POST" action="{{ route('admin.hrm.workloads.decide', $row['workload']) }}" class="d-flex gap-2">@csrf<select name="status" class="form-select form-select-sm"><option value="approved">Setujui</option><option value="rejected">Tolak</option></select><button class="btn btn-success btn-sm">Proses</button></form></td></tr>@empty<tr><td colspan="4"><x-tabler.empty-state title="Tidak ada antrean BKD" description="BKD yang diajukan tampil di sini." icon="ti-check" /></td></tr>@endforelse</tbody></table></div>
</div>
</div></div>
@endsection
