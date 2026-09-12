<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Χαρακτηρισμοί εσόδων κατά myDATA.
 *
 * Υποστηρίζονται δύο συνδυασμοί που καλύπτουν τις συνήθεις περιπτώσεις
 * τιμολογίου πώλησης και παροχής υπηρεσιών. Οι επιτρεπόμενοι συνδυασμοί
 * type/category ορίζονται από το αρχείο «Συνδυασμοί Χαρακτηρισμών» της ΑΑΔΕ.
 */
enum IncomeClassification: string
{
    case GoodsSale = 'goods_sale';
    case ServicesProvision = 'services_provision';

    /** Γραμμή του εντύπου Ε3 όπου καταχωρείται το έσοδο. */
    public function classificationType(): string
    {
        return match ($this) {
            self::GoodsSale => 'E3_561_001',
            self::ServicesProvision => 'E3_561_001',
        };
    }

    /** Κατηγορία εσόδου κατά myDATA. */
    public function classificationCategory(): string
    {
        return match ($this) {
            self::GoodsSale => 'category1_1',
            self::ServicesProvision => 'category1_3',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::GoodsSale => 'Πώληση εμπορευμάτων',
            self::ServicesProvision => 'Παροχή υπηρεσιών',
        };
    }
}
