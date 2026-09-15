@extends('layouts.tabler.admin', ['title' => 'Integration Center'])

@section('content')
<div class="page-header d-print-none"><div class="container-xl"><div class="row align-items-center"><div class="col"><div class="page-pretitle">Integrasi</div><h1 class="page-title">Endpoint, kredensial, log</h1></div></div></div></div>
<div class="page-body"><div class="container-xl">
@if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <form method="POST" action="{{ route('admin.integrations.store') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">Endpoint baru</h3></div><div class="card-body"><label class="form-label">Nama</label><input name="name" class="form-control" required maxlength="255"><label class="form-label mt-2">Jenis</label><select name="kind" class="form-select"><option value="pddikti">PDDikti</option><option value="payment_manual">Gateway manual</option><option value="payment_midtrans">Midtrans</option><option value="payment_xendit">Xendit</option><option value="payment_tripay">Tripay</option><option value="payment_duitku">Duitku</option><option value="whatsapp">WhatsApp</option><option value="smtp">SMTP</option><option value="sso">SSO</option><option value="storage">Storage</option><option value="generic">Generik</option></select><label class="form-label mt-2">Base URL (gunakan fake:// untuk sandbox)</label><input name="base_url" class="form-control" required maxlength="500" value="fake://sandbox.local"><label class="form-label mt-2">Secret (terenkripsi saat simpan)</label><input name="secret" type="password" class="form-control" maxlength="1000"><label class="form-label mt-2">Mode</label><select name="mode" class="form-select"><option value="sandbox">Sandbox</option><option value="live">Live</option></select></div><div class="card-footer"><button class="btn btn-primary w-100">Daftarkan endpoint</button></div></form>
    </div>
    <div class="col-lg-7">
        <div class="card"><div class="card-header"><h3 class="card-title">Endpoint terdaftar</h3></div><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Nama</th><th>Jenis</th><th>Log</th><th class="w-1">Tes</th></tr></thead><tbody>@forelse($endpoints as $endpoint)<tr><td>{{ $endpoint->name }}</td><td><span class="badge bg-azure-lt">{{ $endpoint->kind }}</span></td><td>{{ $endpoint->logs_count }}</td><td><form method="POST" action="{{ route('admin.integrations.test', $endpoint) }}">@csrf<button class="btn btn-sm btn-outline-primary">Tes koneksi</button></form></td></tr>@empty<tr><td colspan="4"><x-tabler.empty-state title="Belum ada endpoint" description="Daftarkan adapter pertama Anda." icon="ti-plug" /></td></tr>@endforelse</tbody></table></div></div>
    </div>
</div>

<div class="card"><div class="card-header"><h3 class="card-title">Log integrasi terbaru</h3></div><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Waktu</th><th>Event</th><th>Kode</th><th>Sukses</th></tr></thead><tbody>@forelse($logs as $log)<tr><td>{{ $log->created_at }}</td><td>{{ $log->event }}</td><td>{{ $log->response_code ?? '-' }}</td><td>{{ $log->success ? 'Ya' : 'Tidak' }}</td></tr>@empty<tr><td colspan="4"><x-tabler.empty-state title="Belum ada log" description="Pemanggilan integrasi tercatat di sini." icon="ti-list" /></td></tr>@endforelse</tbody></table></div>@if($logs->hasPages())<div class="card-footer">{{ $logs->links() }}</div>@endif</div>
</div></div>
@endsection
