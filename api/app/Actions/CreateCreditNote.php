<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Series;
use DomainException;
use Illuminate\Support\Facades\DB;

final class CreateCreditNote
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Invoice $original, array $data): Invoice
    {
        return DB::transaction(function () use ($original, $data): Invoice {
            $this->guard($original);

            /** @var Series $series */
            $series = Series::query()->findOrFail($data['series_id']);

            $creditNote = Invoice::create([
                'company_id' => $original->company_id,
                'customer_id' => $original->customer_id,
                'series_id' => $series->id,
                'document_type' => 'credit_note',
                'issue_date' => $data['issue_date'],
                'related_invoice_id' => $original->id,

                // Το snapshot αντιγράφεται από το αρχικό, όχι από τον πελάτη.
                'customer_name' => $original->customer_name,
                'customer_vat_number' => $original->customer_vat_number,
                'customer_tax_office' => $original->customer_tax_office,
                'customer_address' => $original->customer_address,
                'customer_city' => $original->customer_city,
                'customer_postal_code' => $original->customer_postal_code,
                'customer_country' => $original->customer_country,
            ]);

            $position = 1;

            /** @var array<int, array<string, mixed>> $lines */
            $lines = $data['lines'];

            foreach ($lines as $lineData) {
                $this->createLine($creditNote, $lineData, $position);
                $position++;
            }

            $creditNote->recalculateTotals();
            $creditNote->save();

            return $creditNote;
        });
    }

    private function guard(Invoice $original): void
    {
        if ($original->status === InvoiceStatus::Draft) {
            throw new DomainException(
                'Δεν εκδίδεται πιστωτικό για προσχέδιο. Διαγράψτε ή τροποποιήστε το.'
            );
        }

        if ($original->status === InvoiceStatus::Cancelled) {
            throw new DomainException(
                'Δεν εκδίδεται πιστωτικό για ακυρωμένο παραστατικό.'
            );
        }

        if ($original->document_type !== 'invoice') {
            throw new DomainException(
                'Πιστωτικό εκδίδεται μόνο για τιμολόγιο.'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createLine(Invoice $creditNote, array $data, int $position): void
    {
        $line = new InvoiceLine([
            'invoice_id' => $creditNote->id,
            'position' => $position,
            'description' => $data['description'],
            'unit' => $data['unit'] ?? 'τεμ',
            'quantity' => (string) $data['quantity'],
            'unit_price_cents' => (int) $data['unit_price_cents'],
            'vat_rate' => (int) $data['vat_rate'],
            'income_classification' => $data['income_classification'],
        ]);

        $line->calculateTotals();
        $line->save();
    }
}
