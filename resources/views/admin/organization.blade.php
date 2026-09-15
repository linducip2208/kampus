@extends('layouts.tabler.admin', ['title' => 'Organisasi & Fasilitas'])

@section('content')
<div class="page-header d-print-none"><div class="container-xl"><div class="row align-items-center"><div class="col"><div class="page-pretitle">Organisasi</div><h1 class="page-title">Gedung, ruangan, laboratorium</h1></div></div></div></div>
<div class="page-body"><div class="container-xl">
@if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif

<div class="card mb-4">
    <div class="card-header"><h3 class="card-title"><i class="ti ti-building me-2 text-azure"></i>Gedung</h3></div>
    <div class="table-responsive"><table class="table table-vcenter card-table">
        <thead><tr><th>Gedung</th><th>Kampus</th><th>Lantai</th><th>Ruang</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($buildings as $building)
            <tr><td><div class="fw-semibold">{{ $building->name }}</div><div class="small text-secondary">{{ $building->code }}</div></td><td>{{ $building->campus?->name }}</td><td>{{ $building->floors }}</td><td>{{ $building->rooms->count() }}</td><td><x-tabler.status :value="$building->status" /></td></tr>
        @empty
            <tr><td colspan="5"><x-tabler.empty-state title="Belum ada gedung" description="Tambahkan gedung lewat formulir di bawah." icon="ti-building" /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($buildings->hasPages())<div class="card-footer">{{ $buildings->links() }}</div>@endif
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <form method="POST" action="{{ route('admin.organization.buildings.store') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">Gedung baru</h3></div><div class="card-body"><label class="form-label">Kampus</label><select name="campus_id" class="form-select" required>@foreach($campuses as $campus)<option value="{{ $campus->id }}">{{ $campus->name }}</option>@endforeach</select><label class="form-label mt-2">Nama</label><input name="name" class="form-control" required maxlength="255"><label class="form-label mt-2">Kode</label><input name="code" class="form-control" required maxlength="50"><label class="form-label mt-2">Lantai</label><input type="number" name="floors" value="1" min="1" max="50" class="form-control"></div><div class="card-footer"><button class="btn btn-primary w-100">Simpan gedung</button></div></form>
    </div>
    <div class="col-lg-4">
        <form method="POST" action="{{ route('admin.organization.rooms.store') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">Ruangan baru</h3></div><div class="card-body"><label class="form-label">Gedung</label><select name="building_id" class="form-select" required>@foreach($buildings as $building)<option value="{{ $building->id }}">{{ $building->name }} ({{ $building->code }})</option>@endforeach</select><label class="form-label mt-2">Nama</label><input name="name" class="form-control" required maxlength="255"><label class="form-label mt-2">Kode</label><input name="code" class="form-control" required maxlength="50"><label class="form-label mt-2">Jenis</label><select name="kind" class="form-select"><option value="classroom">Kelas</option><option value="lab">Lab</option><option value="office">Kantor</option><option value="hall">Aula</option><option value="library">Perpustakaan</option><option value="other">Lainnya</option></select><label class="form-label mt-2">Kapasitas</label><input type="number" name="capacity" value="40" min="0" class="form-control"></div><div class="card-footer"><button class="btn btn-primary w-100">Simpan ruangan</button></div></form>
    </div>
    <div class="col-lg-4">
        <div class="card"><div class="card-header"><h3 class="card-title">Laboratorium</h3></div><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Nama</th><th>Status</th></tr></thead><tbody>@forelse($laboratories as $lab)<tr><td><div class="fw-semibold">{{ $lab->name }}</div><div class="small text-secondary">{{ $lab->code }} &middot; {{ $lab->department?->name }}</div></td><td><x-tabler.status :value="$lab->status" /></td></tr>@empty<tr><td colspan="2"><x-tabler.empty-state title="Belum ada laboratorium" description="Laboratorium terdaftar tampil di sini." icon="ti-flask" /></td></tr>@endforelse</tbody></table></div></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="ti ti-door me-2"></i>Ruangan</h3></div>
    <div class="table-responsive"><table class="table table-vcenter card-table">
        <thead><tr><th>Ruangan</th><th>Gedung</th><th>Jenis</th><th>Kapasitas</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($rooms as $room)
            <tr><td><div class="fw-semibold">{{ $room->name }}</div><div class="small text-secondary">{{ $room->code }}</div></td><td>{{ $room->building?->name }}</td><td>{{ $room->kind }}</td><td>{{ $room->capacity }}</td><td><x-tabler.status :value="$room->status" /></td></tr>
        @empty
            <tr><td colspan="5"><x-tabler.empty-state title="Belum ada ruangan" description="Ruangan terdaftar tampil di sini." icon="ti-door" /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($rooms->hasPages())<div class="card-footer">{{ $rooms->links() }}</div>@endif
</div>
</div></div>
@endsection
