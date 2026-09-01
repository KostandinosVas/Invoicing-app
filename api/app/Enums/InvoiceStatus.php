<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Submitting = 'submitting';
    case Submitted = 'submitted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Issued],
            self::Issued => [self::Submitting],
            self::Submitting => [self::Submitted, self::Rejected],
            self::Submitted => [self::Cancelled],
            self::Rejected => [self::Submitting],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }
}
