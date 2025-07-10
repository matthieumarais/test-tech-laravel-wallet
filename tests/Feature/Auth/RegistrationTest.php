<?php

declare(strict_types=1);

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('registration screen can be rendered', function () {
    $response = get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test(
    'fixes the register error when user has no wallet',
    function () {

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(302);

        $user = \App\Models\User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user, 'User should be created');
        $this->assertNotNull($user->wallet, 'User should have a wallet');
        $this->assertEquals(0, $user->wallet->balance, 'Wallet balance should be 0');
    }
);
