@extends('portal.layout', ['heading' => 'Pembelajaran'])

@section('content')
@if(session('success'))
    <x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>
@endif
@if($errors->any())
    <x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>
@endif

<div class="row row-cards">
@forelse($sections as $section)
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="text-secondary small">{{ $section->offering?->course?->code }} &middot; {{ $section->offering?->semester?->name }}</div>
                    <h3 class="card-title">{{ $section->offering?->course?->name }} &middot; {{ $section->code }}</h3>
                </div>
                <div class="card-actions"><span class="badge bg-blue-lt text-blue">{{ ucfirst($section->mode) }}</span></div>
            </div>
            <div class="card-body">
                @if($section->announcements->isNotEmpty())
                    <div class="mb-4">
                        @foreach($section->announcements as $announcement)
                            <div class="alert {{ $announcement->is_pinned ? 'alert-warning' : 'alert-info' }} mb-2">
                                <div class="d-flex gap-2">
                                    <i class="ti {{ $announcement->is_pinned ? 'ti-pin' : 'ti-speakerphone' }} mt-1"></i>
                                    <div><div class="fw-bold">{{ $announcement->title }}</div><div>{!! nl2br(e($announcement->body)) !!}</div><div class="small opacity-75 mt-1">{{ $announcement->author?->name }} &middot; {{ $announcement->published_at?->diffForHumans() }}</div></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="row g-4">
                    <section class="col-12 col-xl-4">
                        <h4><i class="ti ti-books me-2 text-primary"></i>Materi kelas</h4>
                        @forelse($section->courseModules as $module)
                            <div class="mb-3">
                                <div class="fw-semibold">{{ $module->position }}. {{ $module->title }}</div>
                                <div class="list-group list-group-flush mt-1">
                                    @forelse($module->contents as $courseContent)
                                        <div class="list-group-item px-0">
                                            <div class="d-flex gap-2">
                                                <i class="ti {{ $courseContent->type === 'url' ? 'ti-link' : 'ti-file-text' }} mt-1 text-secondary"></i>
                                                <div>
                                                    <div>{{ $courseContent->title }}</div>
                                                    @if($courseContent->body)<div class="small text-secondary">{{ str($courseContent->body)->limit(180) }}</div>@endif
                                                    @if($courseContent->external_url)<a href="{{ $courseContent->external_url }}" target="_blank" rel="noopener noreferrer" class="small">Buka materi <i class="ti ti-external-link"></i></a>@endif
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-secondary small">Belum ada konten terbit.</div>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <x-tabler.empty-state title="Materi belum tersedia" description="Materi yang diterbitkan dosen akan muncul di sini." icon="ti-book-off" />
                        @endforelse
                    </section>

                    <section class="col-12 col-xl-4">
                        <h4><i class="ti ti-clipboard-text me-2 text-warning"></i>Tugas</h4>
                        <div class="list-group list-group-flush">
                            @forelse($section->assignments as $assignment)
                                @php($submission = $assignment->submissions->first())
                                <a href="{{ route('portal.assignments.show', $assignment) }}" class="list-group-item list-group-item-action px-0">
                                    <div class="d-flex justify-content-between gap-3">
                                        <div><div class="fw-semibold">{{ $assignment->title }}</div><div class="small text-secondary">{{ $assignment->due_at ? 'Tenggat '.$assignment->due_at->translatedFormat('d M Y H:i') : 'Tanpa tenggat' }}</div></div>
                                        <span class="badge {{ $submission ? 'bg-green-lt text-green' : 'bg-yellow-lt text-yellow' }}">{{ $submission ? 'Terkirim' : ucfirst($assignment->status) }}</span>
                                    </div>
                                </a>
                            @empty
                                <div class="text-secondary">Belum ada tugas aktif.</div>
                            @endforelse
                        </div>
                        <h4 class="mt-4"><i class="ti ti-help-hexagon me-2 text-azure"></i>Quiz / CBT</h4>
                        <div class="list-group list-group-flush">
                            @forelse($section->quizzes as $quiz)
                                <a href="{{ route('portal.quizzes.show', $quiz) }}" class="list-group-item list-group-item-action px-0">
                                    <div class="d-flex justify-content-between gap-3">
                                        <div><div class="fw-semibold">{{ $quiz->title }}</div><div class="small text-secondary">{{ $quiz->duration_minutes ? $quiz->duration_minutes.' menit' : 'Tanpa batas durasi' }} &middot; {{ $quiz->attempt_limit }} percobaan</div></div>
                                        <span class="badge bg-azure-lt text-azure">{{ ucfirst($quiz->status) }}</span>
                                    </div>
                                </a>
                            @empty
                                <div class="text-secondary">Belum ada quiz aktif.</div>
                            @endforelse
                        </div>
                    </section>

                    <section class="col-12 col-xl-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="mb-0"><i class="ti ti-messages me-2 text-success"></i>Diskusi</h4>
                            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#discussion-{{ $section->id }}"><i class="ti ti-message-plus me-1"></i>Topik</button>
                        </div>
                        <div class="list-group list-group-flush">
                            @forelse($section->discussions as $discussion)
                                <a href="{{ route('portal.discussions.show', $discussion) }}" class="list-group-item list-group-item-action px-0 d-flex justify-content-between gap-3">
                                    <div><div class="fw-semibold">{{ $discussion->title }}</div><div class="small text-secondary">{{ $discussion->creator?->name }} &middot; {{ $discussion->posts_count }} balasan</div></div>
                                    <x-tabler.status :value="$discussion->status" />
                                </a>
                            @empty
                                <x-tabler.empty-state title="Belum ada diskusi" description="Buat topik untuk bertanya atau berbagi pemahaman." icon="ti-message-off" />
                            @endforelse
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <x-tabler.modal id="discussion-{{ $section->id }}" title="Mulai diskusi kelas">
        <form method="POST" action="{{ route('portal.discussions.store', $section) }}">
            @csrf
            <div class="mb-3"><label class="form-label">Topik</label><input name="title" class="form-control" maxlength="160" required></div>
            <div class="mb-3"><label class="form-label">Pertanyaan atau konteks</label><textarea name="body" class="form-control" rows="6" minlength="3" maxlength="20000" required></textarea></div>
            <button class="btn btn-primary w-100"><i class="ti ti-message-plus me-2"></i>Mulai diskusi</button>
        </form>
    </x-tabler.modal>
@empty
    <div class="col-12"><x-tabler.empty-state title="Belum ada kelas pembelajaran" description="Kelas muncul setelah KRS disetujui atau difinalisasi." icon="ti-school-off" /></div>
@endforelse
</div>
@endsection