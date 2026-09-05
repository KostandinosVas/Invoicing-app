<?php

declare(strict_types=1);

use App\Models\User;

it('logs in with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'kostas@test.gr',
        'password' => bcrypt('password'),
    ]);

    $response = test_case()
        ->withHeader('Origin', 'http://localhost:5173')
        ->postJson('/api/login', [
            'email' => 'kostas@test.gr',
            'password' => 'password',
        ]);

    $response->assertOk()
        ->assertJsonPath('email', 'kostas@test.gr');

    test_case()->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'kostas@test.gr',
        'password' => bcrypt('password'),
    ]);

    test_case()->postJson('/api/login', [
        'email' => 'kostas@test.gr',
        'password' => 'wrong',
    ])->assertStatus(422);

    test_case()->assertGuest();
});

it('does not reveal whether an email exists', function () {
    $response = test_case()->postJson('/api/login', [
        'email' => 'unknown@test.gr',
        'password' => 'whatever',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.email.0', 'Τα στοιχεία σύνδεσης δεν είναι σωστά.');
});

it('blocks unauthenticated access to protected routes', function () {
    test_case()->getJson('/api/me')->assertStatus(401);
});

it('returns the authenticated user', function () {
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('id', $user->id);
});
