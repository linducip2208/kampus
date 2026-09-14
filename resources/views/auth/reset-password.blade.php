@extends('layouts.tabler.auth', ['title' => 'Atur kata sandi baru â€” '.$brand['name']])

@section('content')
<x-tabler.card title="Atur kata sandi baru" subtitle="Gunakan kata sandi yang kuat dan berbeda dari layanan lain.">
    <form method="POST" action="{{ route('password.update') }}">@csrf<input type="hidden" name="token" value="{{ $token }}">
        <x-tabler.form-field name="email" label="Email kampus" required><input id="email" name="email" type="email" value="{{ old('email', $email) }}" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" required autocomplete="email"></x-tabler.form-field>
        <x-tabler.form-field name="password" label="Kata sandi baru" required><input id="password" name="password" type="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" required autocomplete="new-password"></x-tabler.form-field>
        <x-tabler.form-field name="password_confirmation" label="Konfirmasi kata sandi" required><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required autocomplete="new-password"></x-tabler.form-field>
        <x-tabler.button type="submit" class="w-100" icon="ti-lock-check">Simpan kata sandi</x-tabler.button>
    </form>
</x-tabler.card>
@endsection
