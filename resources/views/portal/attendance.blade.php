@extends('layouts.tabler.student', ['title' => 'Presensi', 'heading' => 'Presensi perkuliahan'])

@section('content')
@if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif
<div class="row row-cards">
@forelse($plans->flatMap->items as $item)
    @php($meetings = $item->classSection?->meetings ?? collect())
    <div class="col-12 col-xl-6"><x-tabler.card title="{{ $item->classSection?->offering?->course?->name }}"><div class="text-secondary mb-3">{{ $item->classSection?->offering?->course?->code }} · Kelas {{ $item->classSection?->code }}</div>
        <div class="list-group list-group-flush">
        @forelse($meetings as $meeting)
            @php($session = $meeting->attendanceSessions->sortByDesc('created_at')->first())
            @php($mine = $session?->attendances->firstWhere('student_enrollment_id', $enrollment->id))
            <div class="list-group-item px-0"><div class="d-flex align-items-start gap-3"><span class="avatar bg-azure-lt text-azure">{{ $meeting->meeting_number }}</span><div class="flex-fill"><div class="fw-semibold">{{ $meeting->topic ?: 'Pertemuan '.$meeting->meeting_number }}</div><div class="text-secondary small">{{ $meeting->meeting_date->translatedFormat('d M Y') }}</div></div>@if($mine)<x-tabler.status :value="$mine->status" />@elseif($session?->status === 'open' && $session->expires_at?->isFuture())<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#attend-{{ $session->id }}">Presensi</button>@else<span class="badge bg-secondary-lt">Belum tersedia</span>@endif</div></div>
            @if(!$mine && $session?->status === 'open' && $session->expires_at?->isFuture())<x-tabler.modal id="attend-{{ $session->id }}" title="Presensi pertemuan {{ $meeting->meeting_number }}"><form method="POST" action="{{ route('portal.attendance.record', $session) }}">@csrf<div class="mb-3"><label class="form-label required">{{ $session->method === 'pin' ? 'PIN presensi' : 'Token QR' }}</label><input class="form-control" name="credential" required autocomplete="one-time-code"><div class="form-hint">Sesi berakhir {{ $session->expires_at->diffForHumans() }}.</div></div><button class="btn btn-primary w-100">Catat kehadiran</button></form></x-tabler.modal>@endif
        @empty<x-tabler.empty-state title="Belum ada pertemuan" description="Pertemuan akan tampil setelah dibuka dosen." icon="ti-calendar-off" />@endforelse
        </div>
    </x-tabler.card></div>
@empty<div class="col-12"><x-tabler.empty-state title="Belum ada kelas aktif" description="Presensi tersedia setelah KRS disetujui." icon="ti-school-off" /></div>@endforelse
</div>
@endsection
