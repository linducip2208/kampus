@extends('lecturer.layout', ['heading' => 'Kelas saya'])

@section('content')
<div class="reveal"><p class="text-sm font-semibold text-cyan-700">Ruang pengajaran</p><h2 class="mt-1 font-display text-3xl font-bold text-slate-950">Kelas saya</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Kelola konteks kelas dan lihat jumlah mahasiswa yang mengambil mata kuliah Anda.</p></div>
<div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
    @forelse($sections as $section)
        <article class="card-lift rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-start justify-between gap-3"><span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">{{ $section->code }}</span><span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $section->offering->status === 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ ucfirst($section->offering->status) }}</span></div><h3 class="mt-5 font-display text-xl font-bold leading-snug">{{ $section->offering->course->name }}</h3><p class="mt-2 text-sm text-slate-500">{{ $section->offering->course->code }} · {{ $section->offering->course->credits }} SKS</p><div class="mt-5 grid grid-cols-2 gap-3"><div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Mahasiswa</p><p class="mt-1 text-lg font-bold">{{ $section->studyPlanItems->count() }}</p></div><div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Pertemuan</p><p class="mt-1 text-lg font-bold">{{ $section->meetings->count() }}</p></div></div><div class="mt-5 border-t border-slate-100 pt-4 text-xs text-slate-500">{{ $section->room ?: 'Ruang belum ditentukan' }} · {{ $section->schedules->count() }} slot jadwal</div></article>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center md:col-span-2 xl:col-span-3"><p class="font-display text-xl font-bold text-slate-800">Belum ada kelas</p><p class="mt-2 text-sm text-slate-500">Penugasan dari akademik akan tampil setelah disimpan.</p></div>
    @endforelse
</div>
@endsection
