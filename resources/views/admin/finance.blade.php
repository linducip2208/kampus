@extends('layouts.tabler.admin', ['title' => 'Keuangan'])

@section('content')
<div class="page-header d-print-none"><div class="container-xl"><div class="row align-items-center"><div class="col"><div class="page-pretitle">Keuangan</div><h1 class="page-title">Invoice, pembayaran, rekonsiliasi, gateway</h1></div></div></div></div>
<div class="page-body"><div class="container-xl">
@if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3"><x-tabler.stat-card label="Outstanding" :value="$summary['invoices_outstanding']" icon="ti-receipt" color="red" /></div>
    <div class="col-sm-6 col-lg-3"><x-tabler.stat-card label="Nominal outstanding" :value="$summary['outstanding_amount']" icon="ti-cash" color="yellow" /></div>
    <div class="col-sm-6 col-lg-3"><x-tabler.stat-card label="Tanpa alokasi" :value="$summary['unallocated_payments']" icon="ti-alert-triangle" color="azure" /></div>
    <div class="col-sm-6 col-lg-3"><x-tabler.stat-card label="Gateway pending" :value="$summary['pending_gateway']" icon="ti-plug" color="purple" /></div>
</div>

<div class="card mb-4">
    <div class="card-header"><h3 class="card-title"><i class="ti ti-receipt me-2 text-azure"></i>Tagihan terbaru</h3></div>
    <div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Nomor</th><th>Mahasiswa</th><th>Total</th><th>Dibayar</th><th>Status</th><th class="w-1">Tindakan</th></tr></thead><tbody>
    @forelse($invoices as $invoice)
        <tr><td>{{ $invoice->invoice_number }}</td><td>{{ $invoice->enrollment?->studentProfile?->full_name }}</td><td>{{ $invoice->total_amount }}</td><td>{{ $invoice->paid_amount }}</td><td><x-tabler.status :value="$invoice->status" /></td>
        <td><div class="btn-list flex-nowrap"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#discount-{{ $invoice->id }}">Diskon</button><button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#penalty-{{ $invoice->id }}">Denda</button></div>
        <x-tabler.modal id="discount-{{ $invoice->id }}" title="Beri diskon"><form method="POST" action="{{ route('admin.finance.invoices.discount', $invoice) }}">@csrf<label class="form-label">Jenis</label><select name="kind" class="form-select"><option value="scholarship">Beasiswa</option><option value="early_bird">Pelunasan awal</option><option value="staff">Karyawan</option><option value="sibling">Saudara kandung</option><option value="other">Lainnya</option></select><label class="form-label mt-2">Nominal</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" required><button class="btn btn-primary w-100 mt-3">Simpan</button></form></x-tabler.modal>
        <x-tabler.modal id="penalty-{{ $invoice->id }}" title="Terapkan denda"><form method="POST" action="{{ route('admin.finance.invoices.penalty', $invoice) }}">@csrf<label class="form-label">Nominal</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" required><input type="hidden" name="kind" value="late"><button class="btn btn-danger w-100 mt-3">Simpan</button></form></x-tabler.modal></td></tr>
    @empty
        <tr><td colspan="6"><x-tabler.empty-state title="Belum ada tagihan" description="Tagihan dalam scope Anda tampil di sini." icon="ti-receipt" /></td></tr>
    @endforelse
    </tbody></table></div>
    @if($invoices->hasPages())<div class="card-footer">{{ $invoices->links() }}</div>@endif
</div>

<div class="row g-3">
    <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Pembayaran</h3></div><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Nomor</th><th>Nominal</th><th>Status</th></tr></thead><tbody>@forelse($payments as $payment)<tr><td>{{ $payment->payment_number }}</td><td>{{ $payment->amount }}</td><td><x-tabler.status :value="$payment->status" /></td></tr>@empty<tr><td colspan="3"><x-tabler.empty-state title="Belum ada pembayaran" description="Pembayaran tampil di sini." icon="ti-cash" /></td></tr>@endforelse</tbody></table></div></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Transaksi gateway</h3></div><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Provider</th><th>Nominal</th><th>Status</th></tr></thead><tbody>@forelse($transactions as $transaction)<tr><td>{{ $transaction->provider }}</td><td>{{ $transaction->amount }}</td><td><x-tabler.status :value="$transaction->status" /></td></tr>@empty<tr><td colspan="3"><x-tabler.empty-state title="Belum ada transaksi" description="Transaksi gateway tampil di sini." icon="ti-plug" /></td></tr>@endforelse</tbody></table></div></div></div>
</div>
</div></div>
@endsection
