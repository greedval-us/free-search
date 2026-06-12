<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@laravel.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'accept_service_rules' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_rejects_cyrillic_name()
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Тестовый Пользователь',
            'email' => 'test@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'accept_service_rules' => '1',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors(['name']);
    }

    public function test_registration_rejects_invalid_email()
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'accept_service_rules' => '1',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors(['email']);
    }

    public function test_registration_rejects_weak_password()
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'accept_service_rules' => '1',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors(['password']);
    }
}
