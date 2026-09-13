@extends('portal.layout', ['heading' => 'KHS & transkrip'])

@section('content')
<div class="grid gap-4 sm:grid-cols-3">
    @foreach([
        ['IPK kumulatif', number_format($record['gpa'], 2), 'Rata-rata seluruh semester'],
        ['SKS ditempuh', $record['attempted_credits'], 'Total SKS dengan nilai'],
        ['SKS lulus', $record['earned_credits'], 'SKS dengan grade minimal lulus'],
    ] as $stat)
        <div class="card-lift rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">{{ $stat[0] }}</p>
            <p class="mt-3 font-display text-2xl font-bold">{{ $stat[1] }}</p>
            <p class="mt-2 text-xs text-slate-400">{{ $stat[2] }}</p>
        </div>
    @endforeach
</div>

<div class="mt-6 space-y-6">
    @forelse($record['plans'] as $semester)
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-col justify-between gap-3 border-b border-slate-100 p-6 sm:flex-row sm:items-center">
                <div>
                    <p class="text-sm text-slate-500">{{ $semester['plan']->semester?->academicYear?->name }}</p>
                    <h2 class="font-display text-xl font-bold">{{ $semester['plan']->semester?->name }}</h2>
                </div>
                <div class="flex gap-2 text-xs font-semibold">
                    <span class="rounded-lg bg-blue-50 px-3 py-2 text-blue-700">IPS {{ number_format($semester['ips'], 2) }}</span>
                    <span class="rounded-lg bg-slate-100 px-3 py-2 text-slate-600">{{ $semester['earned_credits'] }}/{{ $semester['attempted_credits'] }} SKS lulus</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                        <tr><th class="px-6 py-3">Kode</th><th class="px-6 py-3">Mata kuliah</th><th class="px-6 py-3">SKS</th><th class="px-6 py-3">Nilai</th><th class="px-6 py-3">Bobot</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach($semester['plan']->items as $item)
                        <tr class="hover:bg-blue-50/40">
                            <td class="px-6 py-4 font-mono text-xs text-slate-500">{{ $item->classSection?->offering?->course?->code }}</td>
                            <td class="px-6 py-4 font-semibold">{{ $item->classSection?->offering?->course?->name }}</td>
                            <td class="px-6 py-4">{{ $item->credits }}</td>
                            <td class="px-6 py-4 font-bold">{{ $item->grade?->gradeScale?->grade ?? '—' }}</td>
                            <td class="px-6 py-4">{{ number_format((float) ($item->grade?->gradeScale?->grade_point ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">Belum ada KHS yang dapat ditampilkan.</div>
    @endforelse
</div>

<section class="mt-6 rounded-2xl bg-[#0b1220] p-6 text-white">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div><p class="text-sm text-cyan-300">Transkrip akademik sementara</p><h2 class="mt-1 font-display text-xl font-bold">Rekap hasil studi {{ $enrollment->studentProfile?->full_name }}</h2><p class="mt-2 text-sm text-slate-400">Dokumen final dan QR verifikasi akan tersedia setelah seluruh persyaratan akademik terpenuhi.</p></div>
        <span class="rounded-xl border border-white/10 px-4 py-3 text-sm font-semibold text-slate-300">{{ $record['attempted_credits'] }} SKS tercatat</span>
    </div>
</section>
@endsection
