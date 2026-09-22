<?php

declare(strict_types=1);

use App\Models\User;

it('returns the same user shape from login and me', function () {
    User::factory()->admin()->create([
        'email' => 'admin@example.gr',
        'password' => bcrypt('secret'),
    ]);

    $login = test_case()
        ->withHeader('Origin', 'http://localhost:5173')
        ->postJson('/api/login', [
            'email' => 'admin@example.gr',
            'password' => 'secret',
        ])
        ->assertOk();

    $me = test_case()
        ->withHeader('Origin', 'http://localhost:5173')
        ->getJson('/api/me')
        ->assertOk();

    expect($login->json())->toBe($me->json())
        ->and($login->json('can_write'))->toBeTrue()
        ->and($login->json('can_manage_credentials'))->toBeTrue()
        ->and($login->json())->not->toHaveKey('password');
});
