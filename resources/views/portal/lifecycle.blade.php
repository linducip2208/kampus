@extends('portal.layout', ['heading' => 'Status dan layanan akademik'])

@section('content')
@if(session('success'))
    <x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>
@endif
@if($errors->any())
    <x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>
@endif

<div class="row g-4">
    <div class="col-12 col-lg-4">
        <x-tabler.card title="Status mahasiswa">
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="avatar avatar-lg bg-primary-lt text-primary"><i class="ti ti-user-check fs-1"></i></span>
                <div><div class="h3 mb-1">{{ $enrollment->studentProfile->full_name }}</div><div class="text-secondary">{{ $enrollment->studentProfile->student_number }} &middot; {{ $enrollment->studyProgram->name }}</div></div>
            </div>
            <div class="d-flex justify-content-between align-items-center"><span>Status saat ini</span><x-tabler.status :value="$enrollment->status" /></div>
        </x-tabler.card>

        @if($enrollment->status === 'active')
            <div class="card mt-4">
                <div class="card-header"><h3 class="card-title"><i class="ti ti-calendar-pause me-2 text-warning"></i>Ajukan cuti</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('portal.lifecycle.leave') }}">
                        @csrf
                        <div class="mb-3"><label class="form-label">Semester</label><select name="semester_id" class="form-select" required>@foreach($semesters as $semester)<option value="{{ $semester->id }}">{{ $semester->name }}</option>@endforeach</select></div>
                        <div class="mb-3"><label class="form-label">Alasan</label><textarea name="reason" class="form-control" rows="5" minlength="10" maxlength="5000" required>{{ old('reason') }}</textarea><div class="form-hint">Jelaskan alasan akademik atau personal secara ringkas dan jelas.</div></div>
                        <button class="btn btn-warning w-100" onclick="return confirm('Kirim pengajuan cuti untuk diproses?')"><i class="ti ti-send me-2"></i>Kirim pengajuan cuti</button>
                    </form>
                </div>
            </div>
        @elseif(in_array($enrollment->status, ['leave', 'inactive'], true))
            <div class="card mt-4">
                <div class="card-header"><h3 class="card-title"><i class="ti ti-user-up me-2 text-success"></i>Ajukan aktif kembali</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('portal.lifecycle.reactivate') }}">
                        @csrf
                        <div class="mb-3"><label class="form-label">Semester aktif kembali</label><select name="semester_id" class="form-select" required>@foreach($semesters as $semester)<option value="{{ $semester->id }}">{{ $semester->name }}</option>@endforeach</select></div>
                        <div class="mb-3"><label class="form-label">Alasan</label><textarea name="reason" class="form-control" rows="5" minlength="10" maxlength="5000" required>{{ old('reason') }}</textarea></div>
                        <button class="btn btn-success w-100" onclick="return confirm('Kirim pengajuan aktif kembali?')"><i class="ti ti-send me-2"></i>Kirim pengajuan</button>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <div class="col-12 col-lg-8">
        <x-tabler.card title="Riwayat pengajuan">
            <ul class="nav nav-tabs" data-bs-toggle="tabs">
                <li class="nav-item"><a href="#leave-history" class="nav-link active" data-bs-toggle="tab"><i class="ti ti-calendar-pause me-2"></i>Cuti</a></li>
                <li class="nav-item"><a href="#reactivation-history" class="nav-link" data-bs-toggle="tab"><i class="ti ti-user-up me-2"></i>Aktif kembali</a></li>
                <li class="nav-item"><a href="#status-history" class="nav-link" data-bs-toggle="tab"><i class="ti ti-history me-2"></i>Status</a></li>
            </ul>
            <div class="tab-content pt-3">
                <div class="tab-pane active show" id="leave-history">
                    @forelse($enrollment->leaveRequests->sortByDesc('submitted_at') as $leave)
                        <div class="border rounded p-3 mb-3"><div class="d-flex justify-content-between gap-3"><div><div class="fw-semibold">{{ $leave->semester?->name }}</div><div class="small text-secondary">{{ $leave->submitted_at?->translatedFormat('d M Y H:i') }}</div></div><x-tabler.status :value="$leave->status" /></div><div class="mt-2">{{ $leave->reason }}</div>@if($leave->rejection_reason)<div class="alert alert-danger mt-2 mb-0">{{ $leave->rejection_reason }}</div>@endif</div>
                    @empty
                        <x-tabler.empty-state title="Belum ada pengajuan cuti" description="Riwayat pengajuan dan keputusan akan tampil di sini." icon="ti-calendar-off" />
                    @endforelse
                </div>
                <div class="tab-pane" id="reactivation-history">
                    @forelse($enrollment->reactivationRequests->sortByDesc('submitted_at') as $reactivation)
                        <div class="border rounded p-3 mb-3"><div class="d-flex justify-content-between gap-3"><div><div class="fw-semibold">{{ $reactivation->semester?->name }}</div><div class="small text-secondary">{{ $reactivation->submitted_at?->translatedFormat('d M Y H:i') }}</div></div><x-tabler.status :value="$reactivation->status" /></div><div class="mt-2">{{ $reactivation->reason }}</div>@if($reactivation->rejection_reason)<div class="alert alert-danger mt-2 mb-0">{{ $reactivation->rejection_reason }}</div>@endif</div>
                    @empty
                        <x-tabler.empty-state title="Belum ada pengajuan aktif kembali" description="Pengajuan tersedia ketika status Anda cuti atau nonaktif." icon="ti-user-off" />
                    @endforelse
                </div>
                <div class="tab-pane" id="status-history">
                    @forelse($enrollment->statusHistories->sortByDesc('changed_at') as $history)
                        <div class="d-flex gap-3 mb-3"><span class="avatar avatar-sm bg-blue-lt text-blue"><i class="ti ti-arrow-right"></i></span><div><div><x-tabler.status :value="$history->from_status" /> <i class="ti ti-arrow-right mx-1"></i> <x-tabler.status :value="$history->to_status" /></div><div class="small text-secondary mt-1">{{ $history->changed_at?->translatedFormat('d M Y H:i') }} &middot; {{ $history->changedBy?->name ?? 'Sistem' }}</div><div class="mt-1">{{ $history->reason }}</div></div></div>
                    @empty
                        <x-tabler.empty-state title="Belum ada perubahan status" description="Setiap transisi status resmi dicatat secara immutable." icon="ti-history-off" />
                    @endforelse
                </div>
            </div>
        </x-tabler.card>
    </div>
</div>
@endsection