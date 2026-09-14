@extends('layouts.tabler.auth', ['title' => 'Lupa kata sandi â€” '.$brand['name']])

@section('content')
<div class="text-center mb-4"><a href="{{ route('home') }}" class="navbar-brand navbar-brand-autodark justify-content-center"><span class="avatar bg-primary text-white me-2">{{ str($brand['shortName'])->substr(0,1) }}</span>{{ $brand['shortName'] }}</a></div>
<x-tabler.card title="Pulihkan kata sandi" subtitle="Masukkan email kampus. Tautan pemulihan akan dikirim bila akun ditemukan.">
    @if(session('status'))<x-tabler.alert type="success">{{ session('status') }}</x-tabler.alert>@endif
    <form method="POST" action="{{ route('password.email') }}">@csrf
        <x-tabler.form-field name="email" label="Email kampus" required><div class="input-icon"><span class="input-icon-addon"><i class="ti ti-mail"></i></span><input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" required autofocus autocomplete="email"></div></x-tabler.form-field>
        <x-tabler.button type="submit" class="w-100" icon="ti-send">Kirim tautan pemulihan</x-tabler.button>
    </form>
    <div class="text-center text-secondary mt-3"><a href="{{ route('login') }}"><i class="ti ti-arrow-left me-1"></i>Kembali ke login</a></div>
</x-tabler.card>
@endsection
