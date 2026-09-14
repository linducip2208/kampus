@extends('lecturer.layout', ['heading' => 'Jadwal mengajar'])

@section('content')
<div class="reveal flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="text-sm font-semibold text-cyan-700">Agenda akademik</p><h2 class="mt-1 font-display text-3xl font-bold text-slate-950">Jadwal mengajar</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Lihat kelas, ruang, dan waktu perkuliahan yang ditugaskan kepada Anda.</p></div><span class="rounded-full bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm ring-1 ring-slate-200">{{ $sections->count() }} kelas</span></div>
<div class="mt-6 space-y-5">
    @forelse($sections as $section)
        <section class="card-lift overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col justify-between gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:p-6"><div><div class="flex flex-wrap items-center gap-2"><span class="rounded-lg bg-cyan-50 px-2.5 py-1 text-xs font-bold text-cyan-700">{{ $section->code }}</span><span class="text-xs text-slate-500">{{ $section->offering->semester->name }}</span></div><h3 class="mt-3 font-display text-xl font-bold">{{ $section->offering->course->name }}</h3><p class="mt-1 text-sm text-slate-500">{{ $section->offering->course->code }} · {{ $section->offering->course->credits }} SKS</p></div><span class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">{{ $section->meetings->count() }} pertemuan</span></div>
            <div class="grid divide-y divide-slate-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-3">
                @forelse($section->schedules as $schedule)
                    <div class="p-5"><p class="text-xs font-semibold text-slate-400">{{ [ 'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu' ][$schedule->day_of_week] ?? 'Hari belum diatur' }}</p><p class="mt-2 font-semibold text-slate-800">{{ $schedule->starts_at }}–{{ $schedule->ends_at }}</p><p class="mt-1 text-sm text-slate-500">{{ $schedule->room ?: $section->room ?: 'Ruang belum ditentukan' }}</p></div>
                @empty
                    <div class="p-5 text-sm text-slate-500 sm:col-span-2 lg:col-span-3">Jadwal belum ditetapkan untuk kelas ini.</div>
                @endforelse
            </div>
        </section>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center"><p class="font-display text-xl font-bold text-slate-800">Belum ada kelas mengajar</p><p class="mt-2 text-sm text-slate-500">Penugasan kelas akan muncul di sini setelah operator akademik menetapkan dosen.</p></div>
    @endforelse
</div>
@endsection
