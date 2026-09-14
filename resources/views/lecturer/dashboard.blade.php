@extends('lecturer.layout', ['heading' => 'Ringkasan pengajaran'])

@section('content')
<div class="reveal flex flex-col justify-between gap-5 rounded-3xl bg-[var(--ink)] p-6 text-white shadow-xl shadow-slate-900/10 sm:p-8 lg:flex-row lg:items-end">
    <div>
        <p class="text-sm font-medium text-cyan-300">Selamat datang, {{ Str::before($lecturer->employee?->full_name ?? auth()->user()->name, ' ') }}.</p>
        <h2 class="mt-3 max-w-2xl font-display text-3xl font-bold leading-tight sm:text-4xl">Hari ini, kelas Anda tetap berada dalam konteks.</h2>
        <p class="mt-4 max-w-xl text-sm leading-6 text-slate-300">Pantau jadwal, tindak lanjuti KRS, dan jaga mahasiswa bimbingan dari satu ruang kerja.</p>
    </div>
    <div class="rounded-2xl border border-white/10 bg-white/[.07] px-5 py-4 lg:min-w-48"><p class="text-xs text-slate-400">Jadwal hari ini</p><p class="mt-1 font-display text-3xl font-bold text-cyan-300">{{ $todaySections->count() }} kelas</p></div>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach([
        ['Kelas aktif', $sections->count(), 'Mata kuliah yang Anda ampu', 'bg-cyan-50 text-cyan-700'],
        ['Mahasiswa bimbingan', $advisees->count(), 'Mahasiswa aktif yang tampil', 'bg-blue-50 text-blue-700'],
        ['Menunggu nilai', $pendingGrades, 'Entri nilai berstatus draft', 'bg-amber-50 text-amber-700'],
        ['Pertemuan tercatat', $sections->sum(fn ($section) => $section->meetings->count()), 'Riwayat pertemuan kuliah', 'bg-emerald-50 text-emerald-700'],
    ] as $stat)
        <div class="card-lift rounded-2xl border border-slate-200 bg-white p-5"><div class="flex items-start justify-between gap-3"><p class="text-sm font-medium text-slate-500">{{ $stat[0] }}</p><span class="rounded-lg px-2 py-1 text-xs font-bold {{ $stat[3] }}">●</span></div><p class="mt-4 font-display text-3xl font-bold text-slate-950">{{ $stat[1] }}</p><p class="mt-2 text-xs text-slate-500">{{ $stat[2] }}</p></div>
    @endforeach
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[1.35fr_.65fr]">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-[.12em] text-cyan-700">Agenda</p><h3 class="mt-1 font-display text-xl font-bold">Jadwal hari ini</h3></div><a href="{{ route('lecturer.schedule') }}" class="focus-ring rounded-lg px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">Lihat semua</a></div>
        <div class="mt-5 divide-y divide-slate-100">
            @forelse($todaySections as $section)
                @php($schedule = $section->schedules->first())
                <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between"><div class="flex gap-3"><span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-cyan-500"></span><div><p class="font-semibold text-slate-900">{{ $section->offering->course->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $section->offering->course->code }} · Kelas {{ $section->code }} · {{ $schedule?->room ?: $section->room ?: 'Ruang belum ditentukan' }}</p></div></div><span class="self-start rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">{{ $schedule?->starts_at?->format('H:i') ?? '—' }}–{{ $schedule?->ends_at?->format('H:i') ?? '—' }}</span></div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center"><p class="font-semibold text-slate-800">Tidak ada kelas hari ini</p><p class="mt-1 text-sm text-slate-500">Jadwal berikutnya akan muncul setelah kelas dan jadwal ditetapkan.</p></div>
            @endforelse
        </div>
    </section>
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex items-center justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-[.12em] text-blue-700">Perlu perhatian</p><h3 class="mt-1 font-display text-xl font-bold">Mahasiswa bimbingan</h3></div><a href="{{ route('lecturer.advisees') }}" class="focus-ring rounded-lg px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">Buka</a></div>
        <div class="mt-5 space-y-3">
            @forelse($advisees as $advisee)
                <div class="flex items-center gap-3 rounded-xl border border-slate-100 p-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-100 font-bold text-slate-600">{{ Str::substr($advisee->studentProfile->full_name, 0, 1) }}</span><div class="min-w-0"><p class="truncate text-sm font-semibold">{{ $advisee->studentProfile->full_name }}</p><p class="truncate text-xs text-slate-500">{{ $advisee->studentProfile->student_number }} · {{ $advisee->studyProgram->code }}</p></div></div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-5 text-sm text-slate-500">Belum ada mahasiswa bimbingan aktif.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
