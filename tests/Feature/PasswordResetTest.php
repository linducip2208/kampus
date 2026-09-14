<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_and_complete_a_password_reset(): void
    {
        Notification::fake();
        $this->seed();

        $user = User::query()->where('email', 'baak@kampus.test')->firstOrFail();

        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Lupa kata sandi');

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $this->get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))
                ->assertOk()
                ->assertSee('Atur kata sandi baru');

            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'Password-Baru-2026',
                'password_confirmation' => 'Password-Baru-2026',
            ])->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('Password-Baru-2026', $user->fresh()->password));
    }
}
