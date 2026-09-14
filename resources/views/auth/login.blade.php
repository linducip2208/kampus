@extends('layouts.tabler.auth', ['title' => 'Masuk — Portal Universitas'])

@section('content')
<div class="text-center mb-4 d-lg-none">
    <a href="{{ route('home') }}" class="navbar-brand navbar-brand-autodark justify-content-center">
        <span class="avatar bg-primary text-white me-2">{{ str($brand['shortName'])->substr(0, 1) }}</span>{{ $brand['shortName'] }}
    </a>
</div>
<div class="card card-md shadow-sm">
    <div class="card-body">
        <h1 class="h2 text-center mb-2">Masuk</h1>
        <p class="text-secondary text-center mb-4">Gunakan akun kampus untuk membuka ruang kerja sesuai peran.</p>

        @if(session('status'))<x-tabler.alert type="success">{{ session('status') }}</x-tabler.alert>@endif
        @if($errors->any())<x-tabler.alert type="danger" title="Login belum berhasil">{{ $errors->first() }}</x-tabler.alert>@endif

        <form method="POST" action="{{ route('login.store') }}" autocomplete="on">
            @csrf
            <x-tabler.form-field name="email" label="Email kampus" required>
                <div class="input-icon"><span class="input-icon-addon"><i class="ti ti-mail"></i></span><input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" placeholder="nama@kampus.test" required autofocus autocomplete="email"></div>
            </x-tabler.form-field>
            <x-tabler.form-field name="password" label="Kata sandi" required>
                <div class="input-group input-group-flat"><span class="input-group-text"><i class="ti ti-lock"></i></span><input id="password" name="password" type="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" required autocomplete="current-password"><span class="input-group-text"><button type="button" class="btn btn-link p-0" data-password-toggle aria-label="Tampilkan kata sandi"><i class="ti ti-eye"></i></button></span></div>
            </x-tabler.form-field>
            <div class="d-flex align-items-center justify-content-between mb-3">
                <label class="form-check mb-0"><input class="form-check-input" type="checkbox" name="remember" value="1"><span class="form-check-label">Ingat perangkat ini</span></label>
                <a href="{{ route('password.request') }}">Lupa kata sandi?</a>
            </div>
            <x-tabler.button type="submit" class="w-100" icon="ti-login">Masuk ke portal</x-tabler.button>
        </form>
    </div>
    <div class="card-footer bg-body-tertiary">
        <div class="fw-semibold mb-2"><i class="ti ti-flask me-1 text-primary"></i>Akun demo</div>
        <div class="table-responsive"><table class="table table-sm table-borderless mb-0"><tbody>
            @foreach([['Admin','admin@kampus.test'],['Rektor','rektor@kampus.test'],['BAAK','baak@kampus.test'],['Finance','finance@kampus.test'],['Dosen','dosen@kampus.test'],['Mahasiswa','mahasiswa@kampus.test']] as $account)
                <tr><td class="fw-semibold ps-0">{{ $account[0] }}</td><td class="font-monospace text-secondary">{{ $account[1] }}</td><td class="font-monospace text-secondary pe-0">password</td></tr>
            @endforeach
        </tbody></table></div>
    </div>
</div>
<div class="text-center text-secondary mt-3"><a href="{{ route('home') }}"><i class="ti ti-arrow-left me-1"></i>Kembali ke halaman depan</a></div>
<script>document.querySelector('[data-password-toggle]')?.addEventListener('click', function () { const input=document.getElementById('password'); const visible=input.type==='text'; input.type=visible?'password':'text'; this.setAttribute('aria-label', visible?'Tampilkan kata sandi':'Sembunyikan kata sandi'); this.querySelector('i').className=visible?'ti ti-eye':'ti ti-eye-off'; });</script>
@endsection