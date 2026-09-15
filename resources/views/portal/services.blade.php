@extends('portal.layout', ['heading' => 'Layanan mandiri'])

@section('content')
@if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif

<div class="row g-3">
    <div class="col-lg-6">
        <x-tabler.card title="Tugas akhir saya">
            @forelse($theses as $thesis)
                <div class="d-flex justify-content-between border-bottom py-2"><span>{{ str($thesis->title)->limit(60) }}</span><x-tabler.status :value="$thesis->status" /></div>
            @empty
                <x-tabler.empty-state title="Belum ada proposal" description="Ajukan judul penelitian Anda." icon="ti-book" />
            @endforelse
            <form method="POST" action="{{ route('portal.services.thesis') }}" class="mt-3">@csrf<label class="form-label">Judul baru</label><input name="title" class="form-control" minlength="10" maxlength="500" required><label class="form-label mt-2">Abstrak</label><textarea name="abstract" class="form-control" rows="3" maxlength="5000"></textarea><button class="btn btn-primary w-100 mt-3">Ajukan proposal</button></form>
        </x-tabler.card>
    </div>
    <div class="col-lg-6">
        <x-tabler.card title="Surat dan MBKM">
            <div class="fw-semibold mb-2">Surat saya ({{ $letters->count() }})</div>
            @forelse($letters as $letter)
                <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $letter->template?->name }} &middot; {{ $letter->document_number ?? 'diproses' }}</span><x-tabler.status :value="$letter->status" /></div>
            @empty
                <div class="text-secondary small mb-2">Belum ada pengajuan surat.</div>
            @endforelse
            <form method="POST" action="{{ route('portal.services.letters') }}" class="mt-2">@csrf<div class="row g-2"><div class="col-7"><select name="letter_template_id" class="form-select" required>@foreach($templates as $template)<option value="{{ $template->id }}">{{ $template->name }}</option>@endforeach</select></div><div class="col-5"><button class="btn btn-outline-primary w-100">Ajukan surat</button></div></div></form>
            <hr>
            <div class="fw-semibold mb-2">MBKM saya ({{ $mbkm->count() }})</div>
            <form method="POST" action="{{ route('portal.services.mbkm') }}">@csrf<div class="row g-2"><div class="col-7"><select name="mbkm_program_id" class="form-select" required>@foreach($programs as $program)<option value="{{ $program->id }}">{{ $program->name }}</option>@endforeach</select></div><div class="col-5"><button class="btn btn-outline-primary w-100">Daftar MBKM</button></div></div></form>
        </x-tabler.card>
    </div>
    <div class="col-lg-6">
        <x-tabler.card title="Perpustakaan">
            @forelse($loans as $loan)
                <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $loan->book?->title }}</span><x-tabler.status :value="$loan->status" /></div>
            @empty
                <div class="text-secondary small mb-2">Belum ada pinjaman.</div>
            @endforelse
            <form method="POST" action="{{ route('portal.services.library') }}" class="mt-2">@csrf<div class="row g-2"><div class="col-7"><select name="library_book_id" class="form-select" required>@foreach($books as $book)<option value="{{ $book->id }}">{{ str($book->title)->limit(50) }} ({{ $book->copies_available }})</option>@endforeach</select></div><div class="col-5"><button class="btn btn-outline-primary w-100">Pinjam</button></div></div></form>
        </x-tabler.card>
    </div>
    <div class="col-lg-6">
        <x-tabler.card title="Beasiswa, mutasi, cicilan">
            @forelse($awards as $award)
                <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $award->scholarship?->name }}</span><x-tabler.status :value="$award->status" /></div>
            @empty
                <div class="text-secondary small">Belum ada beasiswa.</div>
            @endforelse
            <div class="small text-secondary mt-2">Mutasi: {{ $transfers->count() }} pengajuan &middot; Tagihan: {{ $enrollment->invoices->count() }} invoice</div>
        </x-tabler.card>
    </div>
</div>
@endsection
