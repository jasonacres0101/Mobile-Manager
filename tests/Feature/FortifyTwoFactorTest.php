<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Fortify;
use Tests\TestCase;

class FortifyTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_displays_two_factor_controls(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Two-factor authentication')
            ->assertSee('Enable 2FA');
    }

    public function test_user_can_start_two_factor_setup(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('two-factor.enable'))
            ->assertRedirect();

        $user->refresh();

        $this->assertNotNull($user->two_factor_secret);
        $this->assertNotNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_login_redirects_to_two_factor_challenge_when_enabled(): void
    {
        $user = User::factory()->create([
            'email' => 'secure@example.com',
            'password' => bcrypt('Password123!'),
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('test-secret'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post(route('login'), [
            'email' => 'secure@example.com',
            'password' => 'Password123!',
        ])
            ->assertRedirect(route('two-factor.login'));

        $this->assertGuest();
        $this->assertSame($user->id, session('login.id'));
    }
}
