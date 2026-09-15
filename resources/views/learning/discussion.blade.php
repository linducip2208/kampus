@extends($layout, ['title' => $heading, 'heading' => $heading])

@section('content')
@if(session('success'))
    <x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>
@endif
@if($errors->any())
    <x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>
@endif

<div class="mb-3">
    <a href="{{ $backRoute }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>Kembali ke pembelajaran</a>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <div>
                    <div class="text-secondary small">{{ $discussion->classSection?->offering?->course?->code }} &middot; {{ $discussion->classSection?->code }}</div>
                    <h3 class="card-title">{{ $discussion->title }}</h3>
                </div>
                <div class="card-actions"><x-tabler.status :value="$discussion->status" /></div>
            </div>
            <div class="card-body">
                <div class="d-flex gap-3">
                    <x-tabler.avatar :name="$discussion->creator?->name ?? 'Pengguna'" />
                    <div>
                        <div class="fw-semibold">{{ $discussion->creator?->name }}</div>
                        <div class="small text-secondary mb-3">{{ $discussion->created_at?->translatedFormat('d M Y H:i') }}</div>
                        <div class="lh-lg">{!! nl2br(e($discussion->body)) !!}</div>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mb-3"><i class="ti ti-messages me-2 text-primary"></i>{{ $discussion->posts->count() }} balasan</h3>
        @forelse($discussion->posts as $post)
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <x-tabler.avatar :name="$post->author?->name ?? 'Pengguna'" />
                        <div class="flex-fill">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="fw-semibold">{{ $post->author?->name }}</span>
                                <span class="small text-secondary">{{ $post->created_at?->diffForHumans() }}</span>
                            </div>
                            <div class="mt-2 lh-lg">{!! nl2br(e($post->body)) !!}</div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <x-tabler.empty-state title="Belum ada balasan" description="Mulai percakapan akademik yang relevan dengan materi kelas." icon="ti-message-off" />
        @endforelse
    </div>

    <div class="col-12 col-lg-4">
        @if($discussion->status === 'open')
            <div class="card position-sticky mb-3" style="top: 5rem">
                <div class="card-header"><h3 class="card-title">Tulis balasan</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ $replyRoute }}">
                        @csrf
                        <label class="form-label" for="discussion-body">Isi balasan</label>
                        <textarea id="discussion-body" name="body" class="form-control" rows="7" minlength="2" maxlength="20000" required>{{ old('body') }}</textarea>
                        <button class="btn btn-primary w-100 mt-3"><i class="ti ti-send me-2"></i>Kirim balasan</button>
                    </form>
                </div>
            </div>
        @else
            <x-tabler.alert type="warning">Diskusi dikunci. Balasan baru tidak dapat dikirim.</x-tabler.alert>
        @endif

        @if($lockRoute)
            <form method="POST" action="{{ $lockRoute }}">
                @csrf
                <input type="hidden" name="locked" value="{{ $discussion->status === 'open' ? 1 : 0 }}">
                <button class="btn {{ $discussion->status === 'open' ? 'btn-outline-danger' : 'btn-outline-success' }} w-100" onclick="return confirm('{{ $discussion->status === 'open' ? 'Kunci diskusi ini?' : 'Buka kembali diskusi ini?' }}')">
                    <i class="ti {{ $discussion->status === 'open' ? 'ti-lock' : 'ti-lock-open' }} me-2"></i>
                    {{ $discussion->status === 'open' ? 'Kunci diskusi' : 'Buka diskusi' }}
                </button>
            </form>
        @endif
    </div>
</div>
@endsection