<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

/**
 * Μόνιμη συνθήκη: η εταιρεία δεν έχει διαπιστευτήρια myDATA.
 * Δεν επιδέχεται retry — απαιτείται παρέμβαση χρήστη.
 */
final class MydataNotConfigured extends DomainException
{
    public static function forCompany(int $companyId): self
    {
        return new self(
            "Η εταιρεία {$companyId} δεν έχει διαπιστευτήρια myDATA."
        );
    }
}
