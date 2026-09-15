@extends('lecturer.layout', ['heading' => 'Beban Kerja Dosen'])

@section('content')
@if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif

<x-tabler.card title="Buka BKD semester">
    <form method="POST" action="{{ route('lecturer.bkd.open') }}" class="row g-2">@csrf<div class="col-8"><select name="semester_id" class="form-select" required>@foreach($semesters as $semester)<option value="{{ $semester->id }}">{{ $semester->name }}</option>@endforeach</select></div><div class="col-4"><button class="btn btn-primary w-100">Buka</button></div></form>
</x-tabler.card>

@foreach($workloads as $row)
<x-tabler.card :title="'BKD ' . ($row['workload']->semester?->name ?? '') . ' — ' . $row['workload']->status">
    <div class="d-flex gap-3 mb-3">
        <span class="badge bg-azure-lt">Total {{ $row['summary']['total_sks'] }} / {{ $row['summary']['target_sks'] }} SKS</span>
        @foreach($row['summary']['by_category'] as $category => $sks)<span class="badge bg-secondary-lt">{{ $category }}: {{ $sks }}</span>@endforeach
        @if($row['summary']['fulfilled'])<span class="badge bg-success-lt">Terpenuhi</span>@endif
    </div>
    @foreach($row['workload']->activities as $activity)
        <div class="d-flex justify-content-between border-bottom py-2"><span><span class="badge bg-secondary-lt me-2">{{ $activity->category }}</span>{{ $activity->title }}</span><strong>{{ $activity->sks }} SKS</strong></div>
    @endforeach
    @if($row['workload']->status === 'draft')
        <form method="POST" action="{{ route('lecturer.bkd.activities.store', $row['workload']) }}" class="row g-2 mt-3">@csrf<div class="col-md-3"><select name="category" class="form-select"><option value="teaching">Pengajaran</option><option value="research">Penelitian</option><option value="service">Pengabdian</option><option value="supporting">Penunjang</option></select></div><div class="col-md-5"><input name="title" class="form-control" placeholder="Judul aktivitas" required maxlength="500"></div><div class="col-md-2"><input type="number" step="0.01" min="0.01" max="12" name="sks" class="form-control" placeholder="SKS" required></div><div class="col-md-2"><button class="btn btn-outline-primary w-100">Tambah</button></div></form>
        <form method="POST" action="{{ route('lecturer.bkd.submit', $row['workload']) }}" class="mt-2">@csrf<button class="btn btn-success w-100" onclick="return confirm('Ajukan BKD?')">Ajukan BKD</button></form>
    @endif
</x-tabler.card>
@endforeach
@endsection
