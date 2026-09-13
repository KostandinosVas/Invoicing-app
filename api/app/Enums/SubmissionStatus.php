<?php

declare(strict_types=1);

namespace App\Enums;

enum SubmissionStatus: string
{
    /** Δημιουργήθηκε, δεν στάλθηκε ακόμα. */
    case Pending = 'pending';

    /** Στάλθηκε, αναμένεται απάντηση. */
    case Sent = 'sent';

    /** Η ΑΑΔΕ το δέχτηκε. */
    case Accepted = 'accepted';

    /** Η ΑΑΔΕ το απέρριψε. Τελικό — απαιτείται διόρθωση. */
    case Rejected = 'rejected';

    /** Τεχνική αποτυχία (δίκτυο, timeout). Επιδέχεται retry. */
    case Failed = 'failed';

    public function isFinal(): bool
    {
        return $this === self::Accepted || $this === self::Rejected;
    }

    public function isRetryable(): bool
    {
        return $this === self::Failed;
    }
}
