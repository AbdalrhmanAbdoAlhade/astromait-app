<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('a user can register and receives a token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Astromait User',
        'email' => 'user@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('user.email', 'user@example.com')
        ->assertJsonPath('user.roles.0', 'user')
        ->assertJsonStructure(['token', 'user']);

    expect(User::where('email', 'user@example.com')->exists())->toBeTrue();
});

test('a user can login, read their profile, and logout', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => 'password123',
    ]);

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => 'login@example.com',
        'password' => 'password123',
    ]);

    $login->assertOk()->assertJsonStructure(['token', 'user']);
    $token = $login->json('token');

    $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('user.id', $user->id);

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    expect(DB::table('personal_access_tokens')->where('token', hash('sha256', $token))->exists())->toBeFalse();
});
