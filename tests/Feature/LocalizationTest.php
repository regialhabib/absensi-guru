<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_login_is_reported_in_indonesian(): void
    {
        $user = User::factory()->guru()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'kata-sandi-salah',
        ])->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
    }

    public function test_validation_messages_use_indonesian_text_and_field_names(): void
    {
        $user = User::factory()->guru()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), ['name' => '', 'email' => 'bukan-email'])
            ->assertSessionHasErrors([
                'name' => 'Nama wajib diisi.',
                'email' => 'Email harus berupa alamat email yang valid.',
            ]);
    }

    public function test_password_reset_status_and_email_are_in_indonesian(): void
    {
        Notification::fake();

        $user = User::factory()->guru()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', 'Tautan reset kata sandi sudah dikirim ke email Anda.');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $mail = $notification->toMail($user);

            return $mail->subject === 'Reset kata sandi Anda' && $mail->actionText === 'Reset Kata Sandi';
        });
    }
}
