<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Κατηγορίες ΦΠΑ κατά myDATA.
 *
 * Η ΑΑΔΕ δεν δέχεται ποσοστό αλλά κωδικό κατηγορίας. Οι τιμές
 * επαληθεύονται κατά του τρέχοντος XSD πριν την πρώτη διαβίβαση.
 */
enum VatCategory: int
{
    case Rate24 = 1;
    case Rate13 = 2;
    case Rate6 = 3;
    case Zero = 7;

    public static function fromRate(int $rate): self
    {
        return match ($rate) {
            24 => self::Rate24,
            13 => self::Rate13,
            6 => self::Rate6,
            0 => self::Zero,
            default => throw new \InvalidArgumentException(
                "Μη υποστηριζόμενος συντελεστής ΦΠΑ: {$rate}"
            ),
        };
    }
}
