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
    public static function forCompany(?string $companyName): self
    {
        $name = $companyName ?? 'άγνωστη';

        return new self(
            "Η εταιρεία «{$name}» δεν έχει καταχωρημένα διαπιστευτήρια myDATA."
        );
    }
}
