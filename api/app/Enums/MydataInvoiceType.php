<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Τύποι παραστατικών κατά myDATA.
 *
 * Οι κωδικοί επαληθεύονται κατά του τρέχοντος XSD πριν την πρώτη διαβίβαση.
 */
enum MydataInvoiceType: string
{
    case SalesInvoice = '1.1';
    case ServicesInvoice = '2.1';
    case CreditNoteCorrelated = '5.1';

    public function label(): string
    {
        return match ($this) {
            self::SalesInvoice => 'Τιμολόγιο Πώλησης',
            self::ServicesInvoice => 'Τιμολόγιο Παροχής Υπηρεσιών',
            self::CreditNoteCorrelated => 'Πιστωτικό Τιμολόγιο (συσχετιζόμενο)',
        };
    }
}
