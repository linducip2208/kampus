@extends('layouts.tabler.admin', ['title' => 'PMB'])

@section('content')
<div class="page-header d-print-none"><div class="container-xl"><div class="row align-items-center"><div class="col"><div class="page-pretitle">Penerimaan</div><h1 class="page-title">Pendaftar</h1></div><div class="col-auto"><form method="GET" class="d-flex gap-2"><select name="status" class="form-select" onchange="this.form.submit()"><option value="">Semua status</option>@foreach(['draft','submitted','payment_pending','payment_verified','document_verification','exam_scheduled','exam','interview','passed','failed','re_registration','student_created'] as $status)<option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>@endforeach</select></form></div></div></div></div>
<div class="page-body"><div class="container-xl">
@if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif

<div class="card">
    <div class="table-responsive"><table class="table table-vcenter card-table">
        <thead><tr><th>Pendaftar</th><th>Jalur</th><th>Status</th><th class="w-1">Tindakan</th></tr></thead>
        <tbody>
        @forelse($applicants as $applicant)
            <tr>
                <td><div class="fw-semibold">{{ $applicant->name }}</div><div class="small text-secondary">{{ $applicant->registration_number }} &middot; {{ $applicant->email }}</div><div class="small text-secondary">{{ $applicant->programChoices->map(fn ($choice) => $choice->studyProgram?->name)->filter()->join(', ') }}</div></td>
                <td>{{ $applicant->admissionPath?->code }}</td>
                <td><x-tabler.status :value="$applicant->status" /></td>
                <td>
                    <div class="btn-list flex-nowrap">
                        @if($applicant->payments->firstWhere('status', 'submitted'))
                            <form method="POST" action="{{ route('admin.pmb.payments.verify', $applicant->payments->firstWhere('status', 'submitted')) }}">@csrf<button class="btn btn-sm btn-success">Verifikasi bayar</button></form>
                        @endif
                        @if($applicant->status === 'payment_verified')
                            <form method="POST" action="{{ route('admin.pmb.documents.verify', $applicant) }}">@csrf<button class="btn btn-sm btn-success">Verifikasi dokumen</button></form>
                        @endif
                        @if($applicant->status === 'interview')
                            <form method="POST" action="{{ route('admin.pmb.decide', $applicant) }}" class="d-flex gap-1">@csrf<input type="hidden" name="passed" value="1"><button class="btn btn-sm btn-success">Lulus</button></form>
                        @endif
                        @if($applicant->status === 're_registration')
                            <form method="POST" action="{{ route('admin.pmb.re-registration.approve', $applicant) }}">@csrf<button class="btn btn-sm btn-success">Setujui daftar ulang</button></form>
                        @endif
                        @if($applicant->status === 're_registration' && $applicant->reRegistration?->status === 'approved')
                            <form method="POST" action="{{ route('admin.pmb.convert', $applicant) }}">@csrf<button class="btn btn-sm btn-primary">Buat NIM</button></form>
                        @endif
                    </div>
                    @if($applicant->status === 'document_verification')
                        <form method="POST" action="{{ route('admin.pmb.exams.schedule', $applicant) }}" class="d-flex gap-1 mt-1">@csrf<input type="hidden" name="title" value="Ujian tulis"><input type="date" name="scheduled_at" class="form-control form-control-sm" required><button class="btn btn-sm btn-outline-primary">Jadwalkan</button></form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="4"><x-tabler.empty-state title="Belum ada pendaftar" description="Pendaftar pada scope Anda tampil di sini." icon="ti-user-plus" /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($applicants->hasPages())<div class="card-footer">{{ $applicants->links() }}</div>@endif
</div>
</div></div>
@endsection
