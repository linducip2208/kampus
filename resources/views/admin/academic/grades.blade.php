@extends('layouts.tabler.admin', ['title' => 'Approval Nilai'])

@section('content')
<div class="page-header d-print-none"><div class="container-xl"><x-tabler.page-header title="Approval dan publikasi nilai" description="Tinjau urutan status nilai sebelum tersedia pada KHS dan transkrip." /></div></div>
<div class="page-body"><div class="container-xl">
@if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif
<x-tabler.card title="Permintaan revisi nilai terkunci">
@if($revisions->isEmpty())
<x-tabler.empty-state title="Tidak ada revisi tertunda" description="Permintaan koreksi nilai terkunci dari dosen akan tampil di sini." icon="ti-lock-check" />
@else
<div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Mahasiswa</th><th>Mata kuliah</th><th>Perubahan</th><th>Alasan</th><th>Pengaju</th><th class="w-1">Keputusan</th></tr></thead><tbody>
@foreach($revisions as $revision)<tr>
<td><div class="fw-semibold">{{ $revision->grade?->studyPlanItem?->studyPlan?->enrollment?->studentProfile?->full_name }}</div><div class="text-secondary small">{{ $revision->grade?->studyPlanItem?->studyPlan?->enrollment?->studentProfile?->student_number }}</div></td>
<td>{{ $revision->grade?->studyPlanItem?->classSection?->offering?->course?->name }}</td>
<td><span class="text-secondary">{{ $revision->old_score }}</span> <i class="ti ti-arrow-right mx-1"></i><strong>{{ $revision->new_score }}</strong></td>
<td class="text-wrap" style="min-width:14rem">{{ $revision->reason }}</td><td>{{ $revision->requester?->name }}</td>
<td><form method="POST" action="{{ route('admin.grades.revisions.review',$revision) }}" class="d-flex flex-column gap-2" style="min-width:13rem">@csrf<textarea name="rejection_reason" class="form-control form-control-sm" rows="2" minlength="10" maxlength="2000" placeholder="Alasan wajib untuk penolakan"></textarea><div class="btn-list flex-nowrap"><button name="action" value="approve" class="btn btn-sm btn-success" onclick="return confirm('Setujui revisi dan perbarui nilai terkunci?')"><i class="ti ti-check me-1"></i>Setujui</button><button name="action" value="reject" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tolak permintaan revisi ini?')"><i class="ti ti-x me-1"></i>Tolak</button></div></form></td>
</tr>@endforeach
</tbody></table></div>
@endif
</x-tabler.card>
<x-tabler.card class="mt-3"><form method="GET" class="row g-2 mb-3"><div class="col-12 col-sm-4"><select class="form-select" name="status"><option value="">Semua status</option>@foreach(['draft','submitted','approved','published','locked'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div><div class="col-auto"><button class="btn btn-outline-primary"><i class="ti ti-filter me-2"></i>Filter</button></div></form>
@if($grades->isEmpty())<x-tabler.empty-state title="Tidak ada nilai" description="Nilai yang disubmit dosen akan tampil untuk ditinjau." icon="ti-file-check" />@else<div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Mahasiswa</th><th>Mata kuliah</th><th>Nilai</th><th>Status</th><th class="w-1"></th></tr></thead><tbody>@foreach($grades as $grade)<tr><td><div class="fw-semibold">{{ $grade->studyPlanItem?->studyPlan?->enrollment?->studentProfile?->full_name }}</div><div class="text-secondary small">{{ $grade->studyPlanItem?->studyPlan?->enrollment?->studentProfile?->student_number }}</div></td><td>{{ $grade->studyPlanItem?->classSection?->offering?->course?->name }}</td><td><span class="fw-bold">{{ $grade->final_score ?? '—' }}</span> <span class="badge bg-green-lt text-green">{{ $grade->gradeScale?->grade ?? '—' }}</span></td><td><x-tabler.status :value="$grade->status" /></td><td>@php($next=['submitted'=>'approve','approved'=>'publish','published'=>'lock'][$grade->status]??null)@if($next)<form method="POST" action="{{ route('admin.grades.transition',$grade) }}">@csrf<input type="hidden" name="action" value="{{ $next }}"><button class="btn btn-sm btn-primary" onclick="return confirm('Lanjutkan status nilai ini?')">{{ ['approve'=>'Setujui','publish'=>'Publikasikan','lock'=>'Kunci'][$next] }}</button></form>@endif</td></tr>@endforeach</tbody></table></div>{{ $grades->links() }}@endif
</x-tabler.card></div></div>
@endsection
