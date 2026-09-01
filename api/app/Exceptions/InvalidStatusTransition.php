<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\InvoiceStatus;
use DomainException;

final class InvalidStatusTransition extends DomainException
{
    public static function between(InvoiceStatus $from, InvoiceStatus $to): self
    {
        return new self(
            sprintf('Δεν επιτρέπεται μετάβαση από %s σε %s.', $from->value, $to->value)
        );
    }
}
