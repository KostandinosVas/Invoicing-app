<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\IncomeClassification;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Item;
use Illuminate\Support\Facades\DB;

final class CreateInvoice
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Invoice
    {
        return DB::transaction(function () use ($data): Invoice {
            /** @var Customer $customer */
            $customer = Customer::query()->findOrFail($data['customer_id']);

            $invoice = Invoice::create([
                'company_id' => $data['company_id'],
                'customer_id' => $customer->id,
                'series_id' => $data['series_id'],
                'document_type' => $data['document_type'],
                'issue_date' => $data['issue_date'],
                'customer_name' => $customer->name,
                'customer_vat_number' => $customer->vat_number,
                'customer_tax_office' => $customer->tax_office,
                'customer_address' => $customer->address,
                'customer_city' => $customer->city,
                'customer_postal_code' => $customer->postal_code,
                'customer_country' => $customer->country,
            ]);

            $position = 1;

            /** @var array<int, array<string, mixed>> $lines */
            $lines = $data['lines'];

            foreach ($lines as $lineData) {
                $this->createLine($invoice, $lineData, $position);
                $position++;
            }

            $invoice->recalculateTotals();
            $invoice->save();

            return $invoice;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createLine(Invoice $invoice, array $data, int $position): void
    {
        /** @var Item|null $item */
        $item = isset($data['item_id'])
            ? Item::query()->find($data['item_id'])
            : null;

        $line = new InvoiceLine([
            'invoice_id' => $invoice->id,
            'item_id' => $item?->id,
            'position' => $position,
            'description' => $data['description'] ?? ($item !== null ? $item->name : ''),
            'unit' => $data['unit'] ?? ($item !== null ? $item->unit : 'τεμ'),
            'quantity' => (string) $data['quantity'],
            'unit_price_cents' => $data['unit_price_cents'] ?? ($item !== null ? $item->unit_price_cents : 0),
            'vat_rate' => $data['vat_rate'] ?? ($item !== null ? $item->vat_rate : 24),
            'income_classification' => $data['income_classification']
                ?? ($item !== null ? $item->income_classification : IncomeClassification::ServicesProvision),
        ]);

        $line->calculateTotals();
        $line->save();
    }
}
