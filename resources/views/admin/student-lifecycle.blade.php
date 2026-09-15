@extends('layouts.tabler.admin', ['title' => 'Lifecycle Mahasiswa'])

@section('content')
<div class="page-header d-print-none"><div class="container-xl"><div class="row align-items-center"><div class="col"><div class="page-pretitle">Mahasiswa</div><h1 class="page-title">Cuti dan aktif kembali</h1></div></div></div></div>
<div class="page-body"><div class="container-xl">
@if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif

<div class="card mb-4">
    <div class="card-header"><h3 class="card-title"><i class="ti ti-calendar-pause me-2 text-warning"></i>Pengajuan cuti</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Mahasiswa</th><th>Semester</th><th>Alasan</th><th>Status</th><th class="w-1">Tindakan</th></tr></thead>
            <tbody>
            @forelse($leaveRequests as $leave)
                <tr>
                    <td><div class="fw-semibold">{{ $leave->enrollment?->studentProfile?->full_name }}</div><div class="small text-secondary">{{ $leave->enrollment?->studentProfile?->student_number }} &middot; {{ $leave->enrollment?->studyProgram?->name }}</div></td>
                    <td>{{ $leave->semester?->name }}</td>
                    <td style="min-width: 16rem">{{ str($leave->reason)->limit(140) }}@if($leave->rejection_reason)<div class="small text-danger mt-1">{{ $leave->rejection_reason }}</div>@endif</td>
                    <td><x-tabler.status :value="$leave->status" /></td>
                    <td>
                        @if($leave->status === 'submitted')
                            <div class="btn-list flex-nowrap">
                                <form method="POST" action="{{ route('admin.student-lifecycle.leaves.approve', $leave) }}">@csrf<button class="btn btn-success btn-sm" onclick="return confirm('Setujui cuti dan ubah status mahasiswa?')"><i class="ti ti-check me-1"></i>Setujui</button></form>
                                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#reject-leave-{{ $leave->id }}"><i class="ti ti-x me-1"></i>Tolak</button>
                            </div>
                            <x-tabler.modal id="reject-leave-{{ $leave->id }}" title="Tolak pengajuan cuti">
                                <form method="POST" action="{{ route('admin.student-lifecycle.leaves.reject', $leave) }}">@csrf<label class="form-label">Alasan penolakan</label><textarea name="reason" class="form-control" rows="5" minlength="5" maxlength="5000" required></textarea><button class="btn btn-danger w-100 mt-3">Tolak pengajuan</button></form>
                            </x-tabler.modal>
                        @else
                            <span class="text-secondary small">{{ $leave->processedBy?->name ?? '-' }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><x-tabler.empty-state title="Belum ada pengajuan cuti" description="Pengajuan mahasiswa dalam scope Anda akan tampil di sini." icon="ti-calendar-off" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($leaveRequests->hasPages())<div class="card-footer">{{ $leaveRequests->links() }}</div>@endif
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="ti ti-user-up me-2 text-success"></i>Pengajuan aktif kembali</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Mahasiswa</th><th>Semester</th><th>Alasan</th><th>Status</th><th class="w-1">Tindakan</th></tr></thead>
            <tbody>
            @forelse($reactivationRequests as $reactivation)
                <tr>
                    <td><div class="fw-semibold">{{ $reactivation->enrollment?->studentProfile?->full_name }}</div><div class="small text-secondary">{{ $reactivation->enrollment?->studentProfile?->student_number }} &middot; {{ $reactivation->enrollment?->studyProgram?->name }}</div></td>
                    <td>{{ $reactivation->semester?->name }}</td>
                    <td style="min-width: 16rem">{{ str($reactivation->reason)->limit(140) }}@if($reactivation->rejection_reason)<div class="small text-danger mt-1">{{ $reactivation->rejection_reason }}</div>@endif</td>
                    <td><x-tabler.status :value="$reactivation->status" /></td>
                    <td>
                        @if($reactivation->status === 'submitted')
                            <div class="btn-list flex-nowrap">
                                <form method="POST" action="{{ route('admin.student-lifecycle.reactivations.approve', $reactivation) }}">@csrf<button class="btn btn-success btn-sm" onclick="return confirm('Aktifkan kembali mahasiswa ini?')"><i class="ti ti-check me-1"></i>Setujui</button></form>
                                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#reject-reactivation-{{ $reactivation->id }}"><i class="ti ti-x me-1"></i>Tolak</button>
                            </div>
                            <x-tabler.modal id="reject-reactivation-{{ $reactivation->id }}" title="Tolak pengajuan aktif kembali">
                                <form method="POST" action="{{ route('admin.student-lifecycle.reactivations.reject', $reactivation) }}">@csrf<label class="form-label">Alasan penolakan</label><textarea name="reason" class="form-control" rows="5" minlength="5" maxlength="5000" required></textarea><button class="btn btn-danger w-100 mt-3">Tolak pengajuan</button></form>
                            </x-tabler.modal>
                        @else
                            <span class="text-secondary small">{{ $reactivation->processedBy?->name ?? '-' }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><x-tabler.empty-state title="Belum ada pengajuan aktif kembali" description="Pengajuan mahasiswa dalam scope Anda akan tampil di sini." icon="ti-user-off" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($reactivationRequests->hasPages())<div class="card-footer">{{ $reactivationRequests->links() }}</div>@endif
</div>
</div></div>
@endsection