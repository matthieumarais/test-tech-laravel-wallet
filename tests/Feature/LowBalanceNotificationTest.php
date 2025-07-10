<?php

use Illuminate\Support\Facades\Notification;

test('sends low balance notification when balance drops below 10 euros after transaction', function () {
    Notification::fake();

    $user = \App\Models\User::factory()->create();
    $wallet = \App\Models\Wallet::factory()->create([
        'user_id' => $user->id,
        'balance' => 1500, // 15 euros
    ]);

    $action = new \App\Actions\PerformWalletTransaction();
    $action->execute(
        wallet: $wallet,
        type: \App\Enums\WalletTransactionType::DEBIT,
        amount: 600, // 6 euros
        reason: 'Test transaction',
    );

    Notification::assertSentTo(
        $user,
        \App\Notifications\LowBalanceNotification::class
    );
});

test('does not send low balance notification if balance is above 10 euros after transaction', function () {
    Notification::fake();

    $user = \App\Models\User::factory()->create();
    $wallet = \App\Models\Wallet::factory()->create([
        'user_id' => $user->id,
        'balance' => 1500, // 15 euros
    ]);

    $action = new \App\Actions\PerformWalletTransaction();
    $action->execute(
        wallet: $wallet,
        type: \App\Enums\WalletTransactionType::DEBIT,
        amount: 400, // 4 euros
        reason: 'Test transaction',
    );

    Notification::assertNotSentTo(
        $user,
        \App\Notifications\LowBalanceNotification::class
    );
    $this->assertNull($wallet->low_balance_notified_at);
});
