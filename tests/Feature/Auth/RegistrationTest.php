<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '09171234567',
            'position' => 'NURSE',
            'region' => 'Mindanao',
            'province' => 'Surigao Del Norte',
            'city' => 'Placer',
            'barangay' => 'Magupange',
            'street' => 'Poblacion Street',
            'postal_code' => '8405',
            'is_authorized_admin' => '1',
        ]);

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'admin',
        ]);
        $response->assertRedirect(route('login'));
    }
}
