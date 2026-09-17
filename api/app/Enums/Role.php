<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    /** Διαχειριστής γραφείου — πλήρη δικαιώματα. */
    case Admin = 'admin';

    /** Λογιστής — εκδίδει και διαβιβάζει, χωρίς πρόσβαση σε credentials. */
    case Accountant = 'accountant';

    /** Μόνο ανάγνωση. */
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Διαχειριστής',
            self::Accountant => 'Λογιστής',
            self::Viewer => 'Προβολή μόνο',
        };
    }

    public function canWrite(): bool
    {
        return $this !== self::Viewer;
    }

    public function canManageCredentials(): bool
    {
        return $this === self::Admin;
    }
}
