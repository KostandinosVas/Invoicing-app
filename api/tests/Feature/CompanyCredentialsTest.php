<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('stores mydata credentials encrypted', function () {
    $company = Company::factory()->create();

    $company->mydata_user_id = 'testuser';
    $company->mydata_subscription_key = 'abc123secret';
    $company->save();

    // Ο κώδικας βλέπει σκέτο κείμενο.
    expect($company->fresh()?->mydata_user_id)->toBe('testuser');

    // Η βάση βλέπει ciphertext.
    $raw = DB::table('companies')
        ->where('id', $company->id)
        ->value('mydata_subscription_key');

    expect($raw)->not->toBe('abc123secret')
        ->and($raw)->toBeString();
});

it('reports whether credentials are configured', function () {
    $company = Company::factory()->create();

    expect($company->hasMydataCredentials())->toBeFalse();

    $company->mydata_user_id = 'testuser';
    $company->mydata_subscription_key = 'abc123secret';
    $company->save();

    expect($company->fresh()?->hasMydataCredentials())->toBeTrue();
});

it('does not expose credentials through the api', function () {
    $company = Company::factory()->create();
    $company->mydata_user_id = 'testuser';
    $company->mydata_subscription_key = 'abc123secret';
    $company->save();

    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->getJson("/api/companies/{$company->id}")
        ->assertOk()
        ->assertJsonMissingPath('data.mydata_user_id')
        ->assertJsonMissingPath('data.mydata_subscription_key');
});

it('sets credentials through the api without returning them', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $response = test_case()->actingAs($user)
        ->putJson("/api/companies/{$company->id}/credentials", [
            'mydata_user_id' => 'testuser',
            'mydata_subscription_key' => 'abc123secret',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.has_mydata_credentials', true)
        ->assertJsonMissingPath('data.mydata_subscription_key');

    expect($company->fresh()?->mydata_subscription_key)->toBe('abc123secret');
});

it('requires both credential fields', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->putJson("/api/companies/{$company->id}/credentials", [
            'mydata_user_id' => 'testuser',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('mydata_subscription_key');
});
