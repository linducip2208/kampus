@extends('layouts.tabler.admin', ['title' => 'Operasional'])

@section('content')
<div class="page-header d-print-none"><div class="container-xl"><div class="row align-items-center"><div class="col"><div class="page-pretitle">Operasional</div><h1 class="page-title">Dokumen, aset, akuntansi, integrasi</h1></div></div></div></div>
<div class="page-body"><div class="container-xl">
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3"><x-tabler.stat-card label="Mahasiswa" :value="$summary['enrollments_total'] ?? 0" icon="ti-users" color="azure" /></div>
    <div class="col-sm-6 col-lg-3"><x-tabler.stat-card label="Tagihan outstanding" :value="$summary['invoices_outstanding'] ?? 0" icon="ti-receipt" color="red" /></div>
    <div class="col-sm-6 col-lg-3"><x-tabler.stat-card label="Pembayaran" :value="$summary['payments_total'] ?? 0" icon="ti-cash" color="green" /></div>
    <div class="col-sm-6 col-lg-3"><x-tabler.stat-card label="Pinjaman aktif" :value="$summary['library_active_loans'] ?? 0" icon="ti-books" color="yellow" /></div>
</div>

<div class="card mb-4">
    <div class="card-header"><h3 class="card-title"><i class="ti ti-mail me-2"></i>Surat terbaru</h3></div>
    <div class="table-responsive"><table class="table table-vcenter card-table">
        <thead><tr><th>Nomor</th><th>Template</th><th>Mahasiswa</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($letters as $letter)
            <tr><td>{{ $letter->document_number ?? '-' }}</td><td>{{ $letter->template?->name }}</td><td>{{ $letter->enrollment?->studentProfile?->full_name ?? '-' }}</td><td><x-tabler.status :value="$letter->status" /></td></tr>
        @empty
            <tr><td colspan="4"><x-tabler.empty-state title="Belum ada surat" description="Pengajuan surat tampil di sini." icon="ti-mail" /></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="row g-3">
    <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Aset</h3></div><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Nama</th><th>Kode</th><th>Status</th></tr></thead><tbody>@forelse($assets as $asset)<tr><td>{{ $asset->name }}</td><td>{{ $asset->code }}</td><td><x-tabler.status :value="$asset->status" /></td></tr>@empty<tr><td colspan="3"><x-tabler.empty-state title="Belum ada aset" description="Aset terdaftar tampil di sini." icon="ti-box" /></td></tr>@endforelse</tbody></table></div></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Jurnal</h3></div><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Nomor</th><th>Deskripsi</th><th>Baris</th></tr></thead><tbody>@forelse($journals as $journal)<tr><td>{{ $journal->entry_number }}</td><td>{{ str($journal->description)->limit(60) }}</td><td>{{ $journal->lines_count }}</td></tr>@empty<tr><td colspan="3"><x-tabler.empty-state title="Belum ada jurnal" description="Jurnal terposting tampil di sini." icon="ti-book" /></td></tr>@endforelse</tbody></table></div></div></div>
</div>

<div class="card mt-4"><div class="card-header"><h3 class="card-title">Endpoint integrasi</h3></div><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Nama</th><th>Jenis</th><th>Log</th><th>Aktif</th></tr></thead><tbody>@forelse($endpoints as $endpoint)<tr><td>{{ $endpoint->name }}</td><td>{{ $endpoint->kind }}</td><td>{{ $endpoint->logs_count }}</td><td>{{ $endpoint->is_active ? 'Ya' : 'Tidak' }}</td></tr>@empty<tr><td colspan="4"><x-tabler.empty-state title="Belum ada endpoint" description="Daftarkan adapter generik atau fake driver." icon="ti-plug" /></td></tr>@endforelse</tbody></table></div></div>
</div></div>
@endsection
