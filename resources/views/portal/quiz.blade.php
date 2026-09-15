@extends('portal.layout', ['heading' => 'Quiz / CBT'])

@section('content')
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif
<div class="row justify-content-center"><div class="col-12 col-lg-8"><div class="card"><div class="card-body p-4 p-md-5">
<div class="d-flex align-items-start gap-3"><span class="avatar avatar-lg bg-azure-lt text-azure"><i class="ti ti-help-hexagon fs-1"></i></span><div><div class="text-secondary">{{ $quiz->classSection?->offering?->course?->name }}</div><h1 class="h2">{{ $quiz->title }}</h1></div></div>
<div class="mt-4 lh-lg">{!! nl2br(e($quiz->instructions ?: 'Baca setiap pertanyaan dengan teliti sebelum mengirim jawaban.')) !!}</div>
<div class="row g-3 my-4"><div class="col-sm-4"><div class="border rounded p-3"><div class="text-secondary small">Durasi</div><strong>{{ $quiz->duration_minutes ? $quiz->duration_minutes.' menit' : 'Tanpa batas' }}</strong></div></div><div class="col-sm-4"><div class="border rounded p-3"><div class="text-secondary small">Batas percobaan</div><strong>{{ $quiz->attempt_limit }}</strong></div></div><div class="col-sm-4"><div class="border rounded p-3"><div class="text-secondary small">Status</div><x-tabler.status :value="$quiz->status" /></div></div></div>
@if($attempts->firstWhere('status','in_progress'))<a class="btn btn-primary w-100" href="{{ route('portal.quiz-attempts.show',$attempts->firstWhere('status','in_progress')) }}"><i class="ti ti-player-play me-2"></i>Lanjutkan percobaan aktif</a>
@elseif($attempts->count() < $quiz->attempt_limit)<form method="POST" action="{{ route('portal.quizzes.start',$quiz) }}">@csrf<button class="btn btn-primary w-100" onclick="return confirm('Mulai quiz sekarang? Timer berjalan setelah quiz dimulai.')"><i class="ti ti-player-play me-2"></i>Mulai quiz</button></form>
@else<x-tabler.empty-state title="Batas percobaan tercapai" description="Tidak ada percobaan tambahan yang tersedia." icon="ti-lock" />@endif
@if($attempts->isNotEmpty())<hr><h3 class="h4">Riwayat percobaan</h3><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Percobaan</th><th>Status</th><th>Nilai</th><th>Mulai</th></tr></thead><tbody>@foreach($attempts as $attempt)<tr><td>#{{ $attempt->attempt_number }}</td><td><x-tabler.status :value="$attempt->status" /></td><td>{{ $attempt->score ?? '—' }}</td><td>{{ $attempt->started_at->translatedFormat('d M Y H:i') }}</td></tr>@endforeach</tbody></table></div>@endif
</div></div></div></div>
@endsection