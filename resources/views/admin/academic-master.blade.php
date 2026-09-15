@extends('layouts.tabler.admin', ['title' => 'Master Akademik'])

@section('content')
<div class="page-header d-print-none"><div class="container-xl"><div class="row align-items-center"><div class="col"><div class="page-pretitle">Akademik</div><h1 class="page-title">Kalender, libur, kategori, ekuivalensi, aturan</h1></div></div></div></div>
<div class="page-body"><div class="container-xl">
@if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <form method="POST" action="{{ route('admin.academic-master.calendars.store') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">Agenda kalender</h3></div><div class="card-body"><label class="form-label">Judul</label><input name="title" class="form-control" required maxlength="255"><div class="row g-2 mt-2"><div class="col-6"><label class="form-label">Mulai</label><input type="date" name="starts_on" class="form-control" required></div><div class="col-6"><label class="form-label">Selesai</label><input type="date" name="ends_on" class="form-control" required></div></div></div><div class="card-footer"><button class="btn btn-primary w-100">Simpan agenda</button></div></form>
    </div>
    <div class="col-lg-4">
        <form method="POST" action="{{ route('admin.academic-master.holidays.store') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">Hari libur</h3></div><div class="card-body"><label class="form-label">Nama</label><input name="name" class="form-control" required maxlength="255"><label class="form-label mt-2">Tanggal</label><input type="date" name="date" class="form-control" required><label class="form-check mt-2"><input type="checkbox" name="is_national" value="1" class="form-check-input"><span class="form-check-label">Libur nasional</span></label></div><div class="card-footer"><button class="btn btn-primary w-100">Simpan libur</button></div></form>
    </div>
    <div class="col-lg-4">
        <form method="POST" action="{{ route('admin.academic-master.categories.store') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">Kategori MK</h3></div><div class="card-body"><label class="form-label">Nama</label><input name="name" class="form-control" required maxlength="255"><label class="form-label mt-2">Kode</label><input name="code" class="form-control" required maxlength="50"></div><div class="card-footer"><button class="btn btn-primary w-100">Simpan kategori</button></div>
        <div class="card-body border-top">@forelse($categories as $category)<div class="d-flex justify-content-between py-1"><span>{{ $category->name }} <span class="text-secondary">({{ $category->code }})</span></span><span class="badge bg-azure-lt">{{ $category->courses_count }}</span></div>@empty<div class="text-secondary small">Belum ada kategori.</div>@endforelse</div></form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h3 class="card-title"><i class="ti ti-calendar-event me-2 text-azure"></i>Kalender akademik</h3></div>
    <div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Agenda</th><th>Periode</th><th>Jenis</th></tr></thead><tbody>@forelse($calendars as $event)<tr><td>{{ $event->title }}</td><td>{{ $event->starts_on->toDateString() }} &ndash; {{ $event->ends_on->toDateString() }}</td><td>{{ $event->kind }}</td></tr>@empty<tr><td colspan="3"><x-tabler.empty-state title="Belum ada agenda" description="Agenda kalender tampil di sini." icon="ti-calendar" /></td></tr>@endforelse</tbody></table></div>
    @if($calendars->hasPages())<div class="card-footer">{{ $calendars->links() }}</div>@endif
</div>

<div class="row g-3">
    <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Hari libur</h3></div><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Nama</th><th>Tanggal</th><th>Nasional</th></tr></thead><tbody>@forelse($holidays as $holiday)<tr><td>{{ $holiday->name }}</td><td>{{ $holiday->date->toDateString() }}</td><td>{{ $holiday->is_national ? 'Ya' : 'Tidak' }}</td></tr>@empty<tr><td colspan="3"><x-tabler.empty-state title="Belum ada libur" description="Hari libur tampil di sini." icon="ti-sun" /></td></tr>@endforelse</tbody></table></div></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Ekuivalensi MK</h3></div><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Lama</th><th>Baru</th></tr></thead><tbody>@forelse($equivalences as $equivalence)<tr><td>{{ $equivalence->oldCourse?->code }}</td><td>{{ $equivalence->newCourse?->code }}</td></tr>@empty<tr><td colspan="2"><x-tabler.empty-state title="Belum ada ekuivalensi" description="Ekuivalensi mata kuliah tampil di sini." icon="ti-arrows-exchange" /></td></tr>@endforelse</tbody></table></div></div></div>
</div>

<div class="card mt-4"><div class="card-header"><h3 class="card-title">Aturan akademik</h3></div><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Kunci</th><th>Nilai</th></tr></thead><tbody>@forelse($rules as $rule)<tr><td><code>{{ $rule->key }}</code></td><td>{{ json_encode($rule->value) }}</td></tr>@empty<tr><td colspan="2"><x-tabler.empty-state title="Belum ada aturan" description="Aturan konfigurabel tampil di sini." icon="ti-settings" /></td></tr>@endforelse</tbody></table></div></div>
</div></div>
@endsection
