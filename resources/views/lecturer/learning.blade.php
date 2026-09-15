@extends('layouts.tabler.lecturer', ['title' => 'Pembelajaran', 'heading' => 'Kelola pembelajaran'])

@section('content')
@if(session('success'))
    <x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>
@endif
@if($errors->any())
    <x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>
@endif

<div class="card mb-4">
    <div class="card-header">
        <div>
            <h3 class="card-title"><i class="ti ti-database me-2 text-primary"></i>Bank soal</h3>
            <div class="text-secondary small">Pertanyaan reusable untuk quiz dan CBT pada institusi Anda.</div>
        </div>
        <div class="card-actions">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#question-bank-create">
                <i class="ti ti-database-plus me-1"></i>Bank baru
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @forelse($questionBanks as $bank)
                <div class="col-12 col-xl-6">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <div class="fw-bold">{{ $bank->name }}</div>
                                <div class="small text-secondary">{{ $bank->questions->count() }} pertanyaan</div>
                            </div>
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#question-create-{{ $bank->id }}">
                                <i class="ti ti-help-circle me-1"></i>Tambah soal
                            </button>
                        </div>
                        <div class="list-group list-group-flush mt-2">
                            @foreach($bank->questions->take(5) as $question)
                                <div class="list-group-item px-0 d-flex justify-content-between gap-3">
                                    <span class="text-truncate">{{ $question->prompt }}</span>
                                    <span class="badge bg-blue-lt text-blue">{{ str_replace('_', ' ', $question->type) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <x-tabler.modal id="question-create-{{ $bank->id }}" title="Tambah soal ke {{ $bank->name }}" size="lg">
                    <form method="POST" action="{{ route('lecturer.learning.questions.store', $bank) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipe soal</label>
                                <select name="type" class="form-select" required>
                                    <option value="single_choice">Pilihan tunggal</option>
                                    <option value="multiple_answer">Jawaban majemuk</option>
                                    <option value="true_false">Benar / salah</option>
                                    <option value="short_answer">Jawaban singkat</option>
                                    <option value="essay">Essay</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Poin</label>
                                <input type="number" name="points" min=".01" max="1000" step=".01" value="10" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Pertanyaan</label>
                                <textarea name="prompt" rows="4" maxlength="20000" class="form-control" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Opsi, satu per baris</label>
                                <textarea name="options_text" rows="5" maxlength="20000" class="form-control" placeholder="Opsi A&#10;Opsi B"></textarea>
                                <div class="form-hint">Kosongkan untuk jawaban singkat atau essay.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jawaban benar, satu per baris</label>
                                <textarea name="correct_answers" rows="5" maxlength="20000" class="form-control" placeholder="Tulis persis seperti opsi benar"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Penjelasan jawaban</label>
                                <textarea name="explanation" rows="3" maxlength="20000" class="form-control"></textarea>
                            </div>
                        </div>
                        <button class="btn btn-primary w-100 mt-3"><i class="ti ti-device-floppy me-2"></i>Simpan pertanyaan</button>
                    </form>
                </x-tabler.modal>
            @empty
                <div class="col-12"><x-tabler.empty-state title="Belum ada bank soal" description="Buat bank soal pertama sebelum menyusun quiz." icon="ti-database-off" /></div>
            @endforelse
        </div>
    </div>
</div>

<div class="row row-cards">
@forelse($sections as $section)
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="text-secondary small">{{ $section->offering?->course?->code }}</div>
                    <h3 class="card-title">{{ $section->offering?->course?->name }} &middot; {{ $section->code }}</h3>
                </div>
                <div class="card-actions btn-list">
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#module-{{ $section->id }}"><i class="ti ti-folder-plus me-1"></i>Modul</button>
                    <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#assignment-{{ $section->id }}"><i class="ti ti-clipboard-plus me-1"></i>Assignment</button>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#quiz-{{ $section->id }}"><i class="ti ti-help-hexagon me-1"></i>Quiz</button>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <section class="col-12 col-xl-4">
                        <h4><i class="ti ti-folders me-2 text-primary"></i>Modul dan materi</h4>
                        @forelse($section->courseModules as $module)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between gap-2">
                                    <div><strong>{{ $module->position }}. {{ $module->title }}</strong><div><x-tabler.status :value="$module->status" /></div></div>
                                    <button class="btn btn-sm btn-icon btn-ghost-primary" data-bs-toggle="modal" data-bs-target="#content-{{ $module->id }}" aria-label="Tambah konten"><i class="ti ti-file-plus"></i></button>
                                </div>
                                <ul class="list-unstyled mt-3 mb-0">
                                    @forelse($module->contents as $courseContent)
                                        <li class="py-1"><i class="ti {{ $courseContent->type === 'url' ? 'ti-link' : 'ti-file-text' }} me-2 text-secondary"></i>{{ $courseContent->title }} @if(!$courseContent->published_at)<span class="badge bg-yellow-lt text-yellow">Draft</span>@endif</li>
                                    @empty
                                        <li class="text-secondary small">Belum ada konten.</li>
                                    @endforelse
                                </ul>
                            </div>
                            <x-tabler.modal id="content-{{ $module->id }}" title="Tambah konten {{ $module->title }}">
                                <form method="POST" action="{{ route('lecturer.learning.contents.store', $module) }}">
                                    @csrf
                                    <div class="mb-3"><label class="form-label">Jenis</label><select name="type" class="form-select" required><option value="text">Teks</option><option value="url">Tautan</option></select></div>
                                    <div class="mb-3"><label class="form-label">Judul</label><input name="title" class="form-control" maxlength="160" required></div>
                                    <div class="mb-3"><label class="form-label">Isi teks</label><textarea name="body" class="form-control" rows="5"></textarea></div>
                                    <div class="mb-3"><label class="form-label">URL eksternal</label><input type="url" name="external_url" class="form-control"></div>
                                    <label class="form-check mb-3"><input type="hidden" name="publish_now" value="0"><input type="checkbox" name="publish_now" value="1" class="form-check-input"><span class="form-check-label">Terbitkan sekarang</span></label>
                                    <button class="btn btn-primary w-100">Simpan konten</button>
                                </form>
                            </x-tabler.modal>
                        @empty
                            <x-tabler.empty-state title="Belum ada modul" description="Buat modul agar materi tersusun per topik." icon="ti-folders" />
                        @endforelse
                    </section>

                    <section class="col-12 col-xl-4">
                        <h4><i class="ti ti-clipboard-text me-2 text-warning"></i>Assignment</h4>
                        @forelse($section->assignments as $assignment)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between"><div><strong>{{ $assignment->title }}</strong><div class="small text-secondary">{{ $assignment->due_at?->translatedFormat('d M Y H:i') ?? 'Tanpa tenggat' }}</div></div><x-tabler.status :value="$assignment->status" /></div>
                                <div class="mt-3">
                                    @forelse($assignment->submissions as $submission)
                                        <form method="POST" action="{{ route('lecturer.learning.submissions.grade', $submission) }}" class="border-top py-3">
                                            @csrf
                                            <div class="fw-semibold">{{ $submission->enrollment?->studentProfile?->full_name }}</div>
                                            <div class="small text-secondary text-truncate mb-2">{{ $submission->answer_text }}</div>
                                            <div class="row g-2">
                                                <div class="col-4"><input type="number" name="score" class="form-control form-control-sm" min="0" max="{{ $assignment->max_score }}" step=".01" value="{{ $submission->score }}" placeholder="Nilai" required></div>
                                                <div class="col"><input name="feedback" class="form-control form-control-sm" maxlength="5000" value="{{ $submission->feedback }}" placeholder="Umpan balik"></div>
                                                <div class="col-auto"><button class="btn btn-sm btn-success" onclick="return confirm('Simpan dan kunci nilai submission?')" aria-label="Simpan nilai"><i class="ti ti-check"></i></button></div>
                                            </div>
                                        </form>
                                    @empty
                                        <div class="text-secondary small">Belum ada submission.</div>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <x-tabler.empty-state title="Belum ada assignment" description="Buat assignment dengan tenggat dan batas percobaan." icon="ti-clipboard-off" />
                        @endforelse
                    </section>

                    <section class="col-12 col-xl-4">
                        <h4><i class="ti ti-help-hexagon me-2 text-azure"></i>Quiz dan CBT</h4>
                        @forelse($section->quizzes as $quiz)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between gap-2">
                                    <div><strong>{{ $quiz->title }}</strong><div class="small text-secondary">{{ $quiz->questions->count() }} soal &middot; {{ $quiz->duration_minutes ?? 'tanpa batas' }} menit</div></div>
                                    <x-tabler.status :value="$quiz->status" />
                                </div>
                                <div class="mt-3">
                                    @forelse($quiz->attempts as $attempt)
                                        <div class="border-top py-3">
                                            <div class="d-flex justify-content-between gap-2">
                                                <div><span class="fw-semibold">{{ $attempt->enrollment?->studentProfile?->full_name }}</span><div class="small text-secondary">Percobaan #{{ $attempt->attempt_number }}</div></div>
                                                <div class="text-end"><x-tabler.status :value="$attempt->status" /><div class="fw-bold mt-1">{{ $attempt->score ?? '-' }}</div></div>
                                            </div>
                                            @if($attempt->status === 'submitted')
                                                @foreach($attempt->answers->filter(fn($item) => in_array($item->question?->type, ['short_answer', 'essay'], true)) as $quizAnswer)
                                                    <form method="POST" action="{{ route('lecturer.learning.quiz-answers.grade', $quizAnswer) }}" class="bg-light rounded p-2 mt-2">
                                                        @csrf
                                                        <div class="small mb-2">{{ $quizAnswer->question?->prompt }}</div>
                                                        <div class="mb-2">{{ $quizAnswer->answer_text }}</div>
                                                        <div class="row g-2">
                                                            <div class="col-3"><input type="number" name="score" class="form-control form-control-sm" min="0" max="{{ $quizAnswer->question?->points }}" step=".01" value="{{ $quizAnswer->score }}" required aria-label="Nilai essay"></div>
                                                            <div class="col"><input name="feedback" class="form-control form-control-sm" maxlength="5000" value="{{ $quizAnswer->feedback }}" placeholder="Umpan balik"></div>
                                                            <div class="col-auto"><button class="btn btn-success btn-sm" onclick="return confirm('Simpan nilai jawaban ini?')" aria-label="Simpan nilai essay"><i class="ti ti-check"></i></button></div>
                                                        </div>
                                                    </form>
                                                @endforeach
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-secondary small mt-3">Belum ada attempt.</div>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <x-tabler.empty-state title="Belum ada quiz" description="Susun quiz dari bank soal institusi." icon="ti-help-off" />
                        @endforelse
                    </section>
                </div>
            </div>
        </div>
    </div>

    <x-tabler.modal id="module-{{ $section->id }}" title="Buat modul">
        <form method="POST" action="{{ route('lecturer.learning.modules.store', $section) }}">
            @csrf
            <div class="mb-3"><label class="form-label">Judul</label><input name="title" class="form-control" maxlength="160" required></div>
            <div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select" required><option value="draft">Draft</option><option value="published">Published</option></select></div>
            <button class="btn btn-primary w-100">Buat modul</button>
        </form>
    </x-tabler.modal>

    <x-tabler.modal id="assignment-{{ $section->id }}" title="Buat assignment" size="lg">
        <form method="POST" action="{{ route('lecturer.learning.assignments.store', $section) }}">
            @csrf
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Judul</label><input name="title" class="form-control" maxlength="160" required></div>
                <div class="col-12"><label class="form-label">Instruksi</label><textarea name="instructions" class="form-control" rows="4"></textarea></div>
                <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="draft">Draft</option><option value="published">Published</option><option value="open">Open</option></select></div>
                <div class="col-md-4"><label class="form-label">Mulai</label><input type="datetime-local" name="opens_at" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Tenggat</label><input type="datetime-local" name="due_at" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Maks. percobaan</label><input type="number" name="max_attempts" class="form-control" min="1" max="10" value="3" required></div>
                <div class="col-md-4"><label class="form-label">Nilai maksimum</label><input type="number" name="max_score" class="form-control" min=".01" max="1000" step=".01" value="100" required></div>
                <div class="col-md-4 d-flex align-items-end"><label class="form-check mb-2"><input type="hidden" name="allow_late" value="0"><input type="checkbox" name="allow_late" value="1" class="form-check-input"><span class="form-check-label">Izinkan terlambat</span></label></div>
            </div>
            <button class="btn btn-primary w-100 mt-3">Buat assignment</button>
        </form>
    </x-tabler.modal>

    <x-tabler.modal id="quiz-{{ $section->id }}" title="Buat quiz / CBT" size="lg">
        <form method="POST" action="{{ route('lecturer.learning.quizzes.store', $section) }}">
            @csrf
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Judul</label><input name="title" class="form-control" maxlength="160" required></div>
                <div class="col-12"><label class="form-label">Instruksi</label><textarea name="instructions" class="form-control" rows="3"></textarea></div>
                <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="draft">Draft</option><option value="published">Published</option><option value="open">Open</option></select></div>
                <div class="col-md-4"><label class="form-label">Mulai</label><input type="datetime-local" name="starts_at" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Selesai</label><input type="datetime-local" name="ends_at" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Durasi (menit)</label><input type="number" name="duration_minutes" min="1" max="1440" value="60" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Maks. attempt</label><input type="number" name="attempt_limit" min="1" max="10" value="1" class="form-control" required></div>
                <div class="col-md-4">
                    <label class="form-label">Pengacakan</label>
                    <label class="form-check"><input type="hidden" name="randomize_questions" value="0"><input type="checkbox" name="randomize_questions" value="1" class="form-check-input"><span class="form-check-label">Acak soal</span></label>
                    <label class="form-check"><input type="hidden" name="randomize_options" value="0"><input type="checkbox" name="randomize_options" value="1" class="form-check-input"><span class="form-check-label">Acak opsi</span></label>
                </div>
                <div class="col-12">
                    <label class="form-label">Pilih soal</label>
                    <div class="border rounded p-3" style="max-height: 18rem; overflow-y: auto">
                        @forelse($questionBanks as $bank)
                            <div class="fw-bold mb-2">{{ $bank->name }}</div>
                            @foreach($bank->questions as $question)
                                <label class="form-check mb-2"><input type="checkbox" name="question_ids[]" value="{{ $question->id }}" class="form-check-input"><span class="form-check-label">{{ $question->prompt }} <span class="text-secondary">({{ $question->points }} poin)</span></span></label>
                            @endforeach
                        @empty
                            <div class="text-secondary">Buat bank dan pertanyaan terlebih dahulu.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <button class="btn btn-primary w-100 mt-3" @disabled($questionBanks->sum(fn($bank) => $bank->questions->count()) === 0)>Buat quiz</button>
        </form>
    </x-tabler.modal>
@empty
    <div class="col-12"><x-tabler.empty-state title="Belum ada kelas" description="Kelas yang Anda ampu akan tampil di sini." icon="ti-school-off" /></div>
@endforelse
</div>

<x-tabler.modal id="question-bank-create" title="Buat bank soal">
    <form method="POST" action="{{ route('lecturer.learning.question-banks.store') }}">
        @csrf
        <div class="mb-3"><label class="form-label">Nama bank</label><input name="name" class="form-control" maxlength="160" required></div>
        <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="4" maxlength="5000"></textarea></div>
        <button class="btn btn-primary w-100"><i class="ti ti-device-floppy me-2"></i>Simpan bank soal</button>
    </form>
</x-tabler.modal>
@endsection