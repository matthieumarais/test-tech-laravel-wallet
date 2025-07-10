<?php

namespace App\Enums;

enum RecurringTransferStatus: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isPaused(): bool
    {
        return $this === self::PAUSED;
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }
}
