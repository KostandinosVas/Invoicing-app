<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;

it('lets an admin list users with their roles', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->accountant()->create();

    test_case()->actingAs($admin)
        ->getJson('/api/users')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'email', 'role']]]);
});

it('blocks a non-admin from listing users', function () {
    $accountant = User::factory()->accountant()->create();

    test_case()->actingAs($accountant)
        ->getJson('/api/users')
        ->assertStatus(403);
});

it('lets an admin change another user role', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->viewer()->create();

    test_case()->actingAs($admin)
        ->putJson("/api/users/{$target->id}/role", ['role' => 'accountant'])
        ->assertOk()
        ->assertJsonPath('data.role', 'accountant');

    expect($target->fresh()?->role())->toBe(Role::Accountant);
});

it('replaces the role instead of adding a second one', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->accountant()->create();

    test_case()->actingAs($admin)
        ->putJson("/api/users/{$target->id}/role", ['role' => 'viewer'])
        ->assertOk();

    expect($target->fresh()?->getRoleNames()->all())->toBe(['viewer']);
});

it('prevents an admin from changing their own role', function () {
    $admin = User::factory()->admin()->create();

    test_case()->actingAs($admin)
        ->putJson("/api/users/{$admin->id}/role", ['role' => 'viewer'])
        ->assertStatus(403);

    expect($admin->fresh()?->role())->toBe(Role::Admin);
});

it('blocks a non-admin from changing roles', function () {
    $accountant = User::factory()->accountant()->create();
    $target = User::factory()->viewer()->create();

    test_case()->actingAs($accountant)
        ->putJson("/api/users/{$target->id}/role", ['role' => 'admin'])
        ->assertStatus(403);

    expect($target->fresh()?->role())->toBe(Role::Viewer);
});

it('rejects an unknown role', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->viewer()->create();

    test_case()->actingAs($admin)
        ->putJson("/api/users/{$target->id}/role", ['role' => 'superuser'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('role');
});
