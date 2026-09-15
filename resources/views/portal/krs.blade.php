@extends('portal.layout', ['heading' => 'Kartu Rencana Studi'])
@section('content')
@php($plan = $enrollment->studyPlans->first())
<x-tabler.card title="Kartu Rencana Studi">
    <x-slot:actions>@if($plan)<x-tabler.status :value="$plan->status" />@endif</x-slot:actions>
    <div class="text-secondary mb-3">{{ $plan?->semester?->name ?? 'Semester belum dipilih' }}</div>
    @if($plan && $plan->items->isNotEmpty())
        <div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Mata kuliah</th><th>Kelas</th><th>SKS</th><th>Nilai</th></tr></thead><tbody>@foreach($plan->items as $item)<tr><td><div class="fw-semibold">{{ $item->classSection->offering->course->name }}</div><div class="text-secondary small">{{ $item->classSection->offering->course->code }}</div></td><td>{{ $item->classSection->code }}</td><td>{{ $item->credits }}</td><td><span class="fw-bold">{{ $item->grade?->gradeScale?->grade ?? '—' }}</span></td></tr>@endforeach</tbody><tfoot><tr><th colspan="2">Total</th><th>{{ $plan->total_credits }} SKS</th><th></th></tr></tfoot></table></div>
    @else
        <x-tabler.empty-state title="Belum ada KRS" description="KRS semester aktif belum memiliki mata kuliah." icon="ti-books-off" />
    @endif
</x-tabler.card>
@endsection