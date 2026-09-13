<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthTokenController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string'], 'device_name' => ['required', 'string', 'max:100']]);
        $user = User::query()->where('email', $data['email'])->first();
        abort_unless($user && Hash::check($data['password'], $user->password), 422, 'Kredensial tidak valid.');
        return response()->json(['token' => $user->createToken($data['device_name'])->plainTextToken, 'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]]);
    }
}
