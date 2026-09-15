@extends('portal.layout', ['heading' => 'Pengerjaan CBT'])

@section('content')
@if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif
<div class="row g-4">
<div class="col-12 col-lg-3"><div class="card position-sticky" style="top:5rem"><div class="card-body"><div class="text-secondary small">Quiz</div><h3 class="card-title">{{ $attempt->quiz->title }}</h3><div class="d-flex justify-content-between mb-3"><span>Percobaan</span><strong>#{{ $attempt->attempt_number }}</strong></div>@if($attempt->status==='in_progress' && $attempt->expires_at)<div class="alert alert-warning"><i class="ti ti-clock me-1"></i>Sisa waktu <strong data-quiz-countdown="{{ $attempt->expires_at->toIso8601String() }}">--:--</strong></div>@endif<div class="d-flex flex-wrap gap-2">@foreach($questionRows as $row)<a href="#question-{{ $row['question']->id }}" class="btn btn-sm {{ $row['answer'] ? 'btn-success' : 'btn-outline-secondary' }}" aria-label="Pertanyaan {{ $loop->iteration }}">{{ $loop->iteration }}</a>@endforeach</div><hr><x-tabler.status :value="$attempt->status" />@if($attempt->status!=='in_progress')<div class="h2 mt-3 mb-0">{{ $attempt->score ?? '-' }}</div><div class="text-secondary small">Nilai sementara</div>@endif</div></div></div>
<div class="col-12 col-lg-9">
@foreach($questionRows as $row)
@php($question = $row['question'])
@php($answer = $row['answer'])
@php($options = $row['options'])
<div class="card mb-4" id="question-{{ $question->id }}"><div class="card-header"><h3 class="card-title">Pertanyaan {{ $loop->iteration }}</h3><div class="card-actions"><span class="badge bg-blue-lt text-blue">{{ $question->pivot->points ?? $question->points }} poin</span></div></div><div class="card-body"><div class="fs-3 mb-4">{!! nl2br(e($question->prompt)) !!}</div>
@if($attempt->status==='in_progress')
<form method="POST" action="{{ route('portal.quiz-attempts.answer',[$attempt,$question]) }}">@csrf
@if(in_array($question->type,['single_choice','true_false']))
@foreach($options as $option)<label class="form-check border rounded p-3 mb-2"><input class="form-check-input" type="radio" name="selected_option_id" value="{{ $option->id }}" @checked(in_array($option->id,$answer?->selected_option_ids ?? [])) required><span class="form-check-label">{{ $option->option_text }}</span></label>@endforeach
@elseif(in_array($question->type,['multiple_choice','multiple_answer']))
@foreach($options as $option)<label class="form-check border rounded p-3 mb-2"><input class="form-check-input" type="checkbox" name="selected_option_ids[]" value="{{ $option->id }}" @checked(in_array($option->id,$answer?->selected_option_ids ?? []))><span class="form-check-label">{{ $option->option_text }}</span></label>@endforeach
@else
<textarea name="answer_text" class="form-control" rows="6" maxlength="20000" required>{{ $answer?->answer_text }}</textarea>
@endif
<button class="btn btn-outline-primary mt-3"><i class="ti ti-device-floppy me-2"></i>Simpan jawaban</button></form>
@else
<div class="alert alert-secondary">Jawaban telah dikirim. @if($answer?->score!==null)Skor: <strong>{{ $answer->score }}</strong>@endif</div>
@endif
</div></div>
@endforeach
@if($attempt->status==='in_progress')<form method="POST" action="{{ route('portal.quiz-attempts.submit',$attempt) }}">@csrf<button class="btn btn-success btn-lg w-100" onclick="return confirm('Kirim quiz? Jawaban tidak dapat diubah setelah dikirim.')"><i class="ti ti-send me-2"></i>Kirim seluruh jawaban</button></form>@else<a href="{{ route('portal.quizzes.show',$attempt->quiz) }}" class="btn btn-outline-primary w-100">Kembali ke ringkasan quiz</a>@endif
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{const el=document.querySelector('[data-quiz-countdown]');if(!el)return;const deadline=new Date(el.dataset.quizCountdown).getTime();const tick=()=>{const left=Math.max(0,deadline-Date.now());const minutes=Math.floor(left/60000);const seconds=Math.floor((left%60000)/1000);el.textContent=String(minutes).padStart(2,'0')+':'+String(seconds).padStart(2,'0');if(left<=0)location.reload()};tick();setInterval(tick,1000)});
</script>
@endsection