@extends('layouts.tabler.admin', ['title' => 'Siklus Akademik Lanjutan'])

@section('content')
<div class="page-header d-print-none"><div class="container-xl"><div class="row align-items-center"><div class="col"><div class="page-pretitle">Akademik</div><h1 class="page-title">Beasiswa, tugas akhir, dan yudisium</h1></div></div></div></div>
<div class="page-body"><div class="container-xl">
@if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif

<div class="card mb-4">
    <div class="card-header"><h3 class="card-title"><i class="ti ti-award me-2 text-yellow"></i>Beasiswa</h3></div>
    <div class="table-responsive"><table class="table table-vcenter card-table">
        <thead><tr><th>Mahasiswa</th><th>Beasiswa</th><th>Nominal</th><th>Status</th><th class="w-1">Tindakan</th></tr></thead>
        <tbody>
        @forelse($scholarships as $award)
            <tr>
                <td><div class="fw-semibold">{{ $award->enrollment?->studentProfile?->full_name }}</div><div class="small text-secondary">{{ $award->enrollment?->studentProfile?->student_number }}</div></td>
                <td>{{ $award->scholarship?->name }}</td>
                <td>{{ $award->amount }}</td>
                <td><x-tabler.status :value="$award->status" /></td>
                <td>@if($award->status === 'proposed')<form method="POST" action="{{ route('admin.academic-lifecycle.scholarships.approve', $award) }}">@csrf<button class="btn btn-success btn-sm">Setujui</button></form>@else<span class="text-secondary small">-</span>@endif</td>
            </tr>
        @empty
            <tr><td colspan="5"><x-tabler.empty-state title="Belum ada pengajuan beasiswa" description="Pengajuan dalam scope Anda akan tampil di sini." icon="ti-award" /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($scholarships->hasPages())<div class="card-footer">{{ $scholarships->links() }}</div>@endif
</div>

<div class="card mb-4">
    <div class="card-header"><h3 class="card-title"><i class="ti ti-book-2 me-2 text-azure"></i>Tugas akhir</h3></div>
    <div class="table-responsive"><table class="table table-vcenter card-table">
        <thead><tr><th>Mahasiswa</th><th>Judul</th><th>Status</th><th class="w-1">Tindakan</th></tr></thead>
        <tbody>
        @forelse($theses as $thesis)
            <tr>
                <td><div class="fw-semibold">{{ $thesis->enrollment?->studentProfile?->full_name }}</div><div class="small text-secondary">{{ $thesis->enrollment?->studentProfile?->student_number }}</div></td>
                <td style="min-width: 18rem">{{ str($thesis->title)->limit(140) }}</td>
                <td><x-tabler.status :value="$thesis->status" /></td>
                <td>
                    @if($thesis->status === 'submitted')
                        <div class="btn-list flex-nowrap">
                            <form method="POST" action="{{ route('admin.academic-lifecycle.thesis.approve', $thesis) }}">@csrf<button class="btn btn-success btn-sm">Setujui</button></form>
                            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#reject-thesis-{{ $thesis->id }}">Tolak</button>
                        </div>
                        <x-tabler.modal id="reject-thesis-{{ $thesis->id }}" title="Tolak proposal">
                            <form method="POST" action="{{ route('admin.academic-lifecycle.thesis.reject', $thesis) }}">@csrf<label class="form-label">Alasan</label><textarea name="reason" class="form-control" rows="4" minlength="5" required></textarea><button class="btn btn-danger w-100 mt-3">Tolak</button></form>
                        </x-tabler.modal>
                    @else<span class="text-secondary small">-</span>@endif
                </td>
            </tr>
        @empty
            <tr><td colspan="4"><x-tabler.empty-state title="Belum ada proposal" description="Proposal dalam scope Anda akan tampil di sini." icon="ti-book" /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($theses->hasPages())<div class="card-footer">{{ $theses->links() }}</div>@endif
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="ti ti-school me-2 text-success"></i>Yudisium</h3></div>
    <div class="table-responsive"><table class="table table-vcenter card-table">
        <thead><tr><th>Mahasiswa</th><th>IPK</th><th>SKS</th><th>Status</th><th class="w-1">Tindakan</th></tr></thead>
        <tbody>
        @forelse($graduations as $graduation)
            <tr>
                <td><div class="fw-semibold">{{ $graduation->enrollment?->studentProfile?->full_name }}</div><div class="small text-secondary">{{ $graduation->certificate_number ?? $graduation->semester?->name }}</div></td>
                <td>{{ $graduation->gpa }}</td>
                <td>{{ $graduation->total_sks }}</td>
                <td><x-tabler.status :value="$graduation->status" /></td>
                <td>@if($graduation->status === 'proposed')<form method="POST" action="{{ route('admin.academic-lifecycle.graduations.approve', $graduation) }}">@csrf<button class="btn btn-success btn-sm" onclick="return confirm('Setujui yudisium? Transkrip dan alumni otomatis diterbitkan.')">Setujui</button></form>@else<span class="text-secondary small">-</span>@endif</td>
            </tr>
        @empty
            <tr><td colspan="5"><x-tabler.empty-state title="Belum ada pengajuan yudisium" description="Pengajuan dalam scope Anda akan tampil di sini." icon="ti-school" /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($graduations->hasPages())<div class="card-footer">{{ $graduations->links() }}</div>@endif
</div>
</div></div>
@endsection
