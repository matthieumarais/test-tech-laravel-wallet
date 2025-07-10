<?php

namespace App\Models;

use App\Actions\PerformWalletTransaction;
use App\Enums\RecurringTransferStatus;
use App\Enums\WalletTransactionType;
use App\Exceptions\InsufficientBalance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringTransfer extends Model
{
    protected $fillable = [
        'user_id',
        'recipient_email',
        'amount', // Amount in cents
        'reason',
        'start_date',
        'end_date',
        'frequency_days', // Frequency in days
        'status',
        'next_execution_date',
    ];

    protected $casts = [
        'amount' => 'integer', // Store amount as an integer (cents)
        'start_date' => 'date',
        'end_date' => 'date',
        'frequency_days' => 'integer', // Store frequency in days as an integer
        'next_execution_date' => 'datetime',
    ];

    /**
     * Get the user that owns the recurring transfer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_email', 'email');
    }

    public function transferts(): HasMany
    {
        return $this->hasMany(WalletTransfer::class, 'recurring_transfer_id');
    }

    public function shouldExecute(): bool
    {
        return $this->status === RecurringTransferStatus::ACTIVE &&
            $this->next_execution_date <= now() &&
            $this->end_date >= now()->toDateString(); // TODO: check date format
    }

    public function execute(): bool
    {
        if (!$this->shouldExecute()) {
            return false;
        }

        $recipient = $this->recipient;

        try {

            $performWalletTransaction = new PerformWalletTransaction();

            $transfer = new WalletTransfer([
                'amount' => $this->amount,
                'source_id' => $this->user->wallet->id,
                'target_id' => $recipient->wallet->id,
                'recurring_transfer_id' => $this->id,
            ]);

            // transaction de débit
            $debitTransaction = $performWalletTransaction->execute(
                wallet: $this->user->wallet,
                type: WalletTransactionType::DEBIT,
                amount: $this->amount,
                reason: $this->reason,
                transfer: $transfer
            );

            // transaction de crédit
            $creditTransaction = $performWalletTransaction->execute(
                wallet: $recipient->wallet,
                type: WalletTransactionType::CREDIT,
                amount: $this->amount,
                reason: $this->reason,
                transfer: $transfer
            );

            // mettre à jour le transfer avec les id de transaction
            $transfer->update([
                'debit_transaction_id' => $debitTransaction->id,
                'credit_transaction_id' => $creditTransaction->id,
            ]);

            // valider l'execution
            $this->markAsExecuted();

            return true;
        } catch (InsufficientBalance $e) {
            $this->markAsFailed();

            return false;
        } catch (\Exception $e) {
            $this->markAsFailed();
            return false;
        }
    }
}
