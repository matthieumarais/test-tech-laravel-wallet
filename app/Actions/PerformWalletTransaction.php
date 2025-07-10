<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\WalletTransactionType;
use App\Exceptions\InsufficientBalance;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WalletTransfer;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class PerformWalletTransaction
{
    /**
     * @throws InsufficientBalance
     */
    public function execute(Wallet $wallet, WalletTransactionType $type, int $amount, string $reason, ?WalletTransfer $transfer = null, bool $force = false): WalletTransaction
    {
        if (! $force && $type === WalletTransactionType::DEBIT) {
            $this->ensureWalletHasEnoughFunds($wallet, $amount);
        }

        return DB::transaction(function () use ($wallet, $type, $amount, $reason, $transfer) {
            $transaction = $wallet->transactions()->create([
                'amount' => $amount,
                'type' => $type,
                'reason' => $reason,
                'transfer_id' => $transfer?->id,
            ]);

            $this->updateWallet($wallet, $type, $amount);

            return $transaction;
        });
    }

    /**
     * @throws Throwable
     */
    protected function updateWallet(Wallet $wallet, WalletTransactionType $type, int $amount): void
    {
        if ($type === WalletTransactionType::CREDIT) {
            $wallet->increment('balance', $amount);
        } else {
            $wallet->decrement('balance', $amount);
        }

        $this->checkLowBalance($wallet);
    }

    /**
     * @throws InsufficientBalance
     */
    protected function ensureWalletHasEnoughFunds(Wallet $wallet, int $amount): void
    {
        if ($wallet->balance < $amount) {
            throw new InsufficientBalance($wallet, $amount);
        }
    }

    protected function checkLowBalance(Wallet $wallet): void
    {
        if ($wallet->balance < 1000 && $wallet->low_balance_notified_at === null) {
            $wallet->user->notify(new \App\Notifications\LowBalanceNotification());
            $wallet->update(['low_balance_notified_at' => now()]);
        }

        if ($wallet->balance >= 1000 && $wallet->low_balance_notified_at !== null) {
            $wallet->update(['low_balance_notified_at' => null]);
        }
    }
}
