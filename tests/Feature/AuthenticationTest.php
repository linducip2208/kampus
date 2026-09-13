<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_accounts_can_authenticate_and_are_redirected_to_their_workspace(): void
    {
        $this->seed();

        foreach ([
            'admin@kampus.test' => '/admin',
            'baak@kampus.test' => '/admin',
            'finance@kampus.test' => '/admin',
            'dosen@kampus.test' => '/admin',
            'mahasiswa@kampus.test' => '/portal',
        ] as $email => $destination) {
            $response = $this->from('/login')->post('/login', [
                'email' => $email,
                'password' => 'password',
            ]);

            $response->assertRedirect($destination);
            $this->assertAuthenticatedAs(User::query()->where('email', $email)->firstOrFail());
            $this->post('/logout');
        }
    }
}
