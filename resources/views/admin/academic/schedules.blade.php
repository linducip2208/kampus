@extends('layouts.tabler.admin', ['title' => 'Jadwal Kuliah'])

@section('content')
<div class="page-header d-print-none"><div class="container-xl"><x-tabler.page-header title="Jadwal kuliah" description="Kelola slot kelas dengan validasi bentrok ruang, dosen, dan kelas."><x-slot:actions>@if(auth()->user()->hasPermission('class_sections.update'))<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#create-schedule"><i class="ti ti-calendar-plus me-2"></i>Tambah jadwal</button>@endif</x-slot:actions></x-tabler.page-header></div></div>
<div class="page-body"><div class="container-xl">
    @if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
    @if($errors->any())<x-tabler.alert type="danger"><strong>Jadwal belum disimpan.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-tabler.alert>@endif
    <x-tabler.card>
        <form method="GET" class="row g-2 mb-3"><div class="col-12 col-sm-5 col-lg-3"><select name="day" class="form-select" aria-label="Filter hari"><option value="">Semua hari</option>@foreach([1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'] as $value=>$label)<option value="{{ $value }}" @selected(request('day')==$value)>{{ $label }}</option>@endforeach</select></div><div class="col-auto"><button class="btn btn-outline-primary"><i class="ti ti-filter me-2"></i>Terapkan</button></div></form>
        @if($schedules->isEmpty())
            <x-tabler.empty-state title="Belum ada jadwal" description="Tambahkan slot setelah penawaran dan kelas tersedia." icon="ti-calendar-off" />
        @else
            <div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Hari & waktu</th><th>Mata kuliah / kelas</th><th>Ruangan</th><th>Dosen</th><th class="w-1"></th></tr></thead><tbody>
            @foreach($schedules as $schedule)<tr><td><div class="fw-semibold">{{ [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'][$schedule->day_of_week] }}</div><div class="text-secondary">{{ $schedule->starts_at->format('H:i') }}–{{ $schedule->ends_at->format('H:i') }}</div></td><td><div class="fw-semibold">{{ $schedule->classSection?->offering?->course?->name }}</div><div class="text-secondary small">{{ $schedule->classSection?->offering?->course?->code }} · {{ $schedule->classSection?->code }}</div></td><td>{{ $schedule->room ?: 'Online' }}</td><td>{{ $schedule->classSection?->lecturers->map(fn($lecturer) => $lecturer->employee?->full_name)->filter()->join(', ') ?: 'Belum ditetapkan' }}</td><td>@if(auth()->user()->hasPermission('class_sections.update'))<button class="btn btn-sm btn-icon btn-ghost-primary" data-bs-toggle="modal" data-bs-target="#edit-{{ $schedule->id }}" aria-label="Ubah jadwal"><i class="ti ti-edit"></i></button>@endif</td></tr>@endforeach
            </tbody></table></div>{{ $schedules->links() }}
        @endif
    </x-tabler.card>
</div></div>

@if(auth()->user()->hasPermission('class_sections.update'))
<x-tabler.modal id="create-schedule" title="Tambah jadwal kuliah"><form method="POST" action="{{ route('admin.schedules.store') }}">@csrf @include('admin.academic._schedule-fields', ['schedule'=>null])<div class="modal-footer"><button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan jadwal</button></div></form></x-tabler.modal>
@foreach($schedules as $schedule)<x-tabler.modal id="edit-{{ $schedule->id }}" title="Ubah jadwal {{ $schedule->classSection?->code }}"><form method="POST" action="{{ route('admin.schedules.update', $schedule) }}">@csrf @method('PUT') @include('admin.academic._schedule-fields', ['schedule'=>$schedule])<div class="modal-footer"><button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan perubahan</button></div></form></x-tabler.modal>@endforeach
@endif
@endsection
