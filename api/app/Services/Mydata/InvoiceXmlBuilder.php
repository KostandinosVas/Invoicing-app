<?php

declare(strict_types=1);

namespace App\Services\Mydata;

use App\Enums\IncomeClassification;
use App\Enums\VatCategory;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use DOMDocument;
use DOMElement;

final class InvoiceXmlBuilder
{
    private const NS_INVOICE = 'http://www.aade.gr/myDATA/invoice/v1.0';

    private const NS_INCOME = 'https://www.aade.gr/myDATA/incomeClassificaton/v1.0';

    public function build(Invoice $invoice): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $root = $doc->createElementNS(self::NS_INVOICE, 'InvoicesDoc');
        $doc->appendChild($root);

        $root->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:icls',
            self::NS_INCOME,
        );

        $root->appendChild($this->buildInvoice($doc, $invoice));

        return (string) $doc->saveXML();
    }

    private function buildInvoice(DOMDocument $doc, Invoice $invoice): DOMElement
    {
        $node = $doc->createElementNS(self::NS_INVOICE, 'invoice');

        $node->appendChild($this->buildIssuer($doc, $invoice));
        $node->appendChild($this->buildCounterpart($doc, $invoice));
        $node->appendChild($this->buildHeader($doc, $invoice));
        $node->appendChild($this->buildPaymentMethods($doc, $invoice));

        foreach ($invoice->lines as $line) {
            $node->appendChild($this->buildLine($doc, $line));
        }

        $node->appendChild($this->buildSummary($doc, $invoice));

        return $node;
    }

    private function buildIssuer(DOMDocument $doc, Invoice $invoice): DOMElement
    {
        $node = $doc->createElementNS(self::NS_INVOICE, 'issuer');

        $company = $invoice->company;

        $node->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'vatNumber',
            $company !== null ? $company->vat_number : '',
        ));
        $node->appendChild($doc->createElementNS(self::NS_INVOICE, 'country', 'GR'));
        $node->appendChild($doc->createElementNS(self::NS_INVOICE, 'branch', '0'));

        return $node;
    }

    private function buildCounterpart(DOMDocument $doc, Invoice $invoice): DOMElement
    {
        $node = $doc->createElementNS(self::NS_INVOICE, 'counterpart');

        $node->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'vatNumber',
            $invoice->customer_vat_number ?? '',
        ));
        $node->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'country',
            $invoice->customer_country,
        ));
        $node->appendChild($doc->createElementNS(self::NS_INVOICE, 'branch', '0'));

        $address = $doc->createElementNS(self::NS_INVOICE, 'address');
        $address->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'postalCode',
            $invoice->customer_postal_code ?? '',
        ));
        $address->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'city',
            $invoice->customer_city ?? '',
        ));
        $node->appendChild($address);

        return $node;
    }

    private function buildHeader(DOMDocument $doc, Invoice $invoice): DOMElement
    {
        $node = $doc->createElementNS(self::NS_INVOICE, 'invoiceHeader');

        $series = $invoice->series;

        $node->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'series',
            $series !== null ? $series->code : '',
        ));
        $node->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'aa',
            (string) $invoice->number,
        ));
        $node->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'issueDate',
            $invoice->issue_date?->toDateString() ?? '',
        ));
        $type = $invoice->mydata_invoice_type;

        $node->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'invoiceType',
            $type !== null ? $type->value : '',
        ));
        $node->appendChild($doc->createElementNS(self::NS_INVOICE, 'currency', 'EUR'));

        return $node;
    }

    private function buildPaymentMethods(DOMDocument $doc, Invoice $invoice): DOMElement
    {
        $node = $doc->createElementNS(self::NS_INVOICE, 'paymentMethods');

        $details = $doc->createElementNS(self::NS_INVOICE, 'paymentMethodDetails');
        $details->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'type',
            (string) $invoice->payment_method,
        ));
        $details->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'amount',
            $this->amount($invoice->total_cents),
        ));

        $node->appendChild($details);

        return $node;
    }

    /** Λεπτά σε δεκαδική μορφή δύο ψηφίων, όπως απαιτεί το XSD. */
    private function amount(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function buildLine(DOMDocument $doc, InvoiceLine $line): DOMElement
    {
        $node = $doc->createElementNS(self::NS_INVOICE, 'invoiceDetails');

        $node->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'lineNumber',
            (string) $line->position,
        ));
        $node->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'netValue',
            $this->amount($line->net_amount_cents),
        ));
        $node->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'vatCategory',
            (string) VatCategory::fromRate($line->vat_rate)->value,
        ));
        $node->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'vatAmount',
            $this->amount($line->vat_amount_cents),
        ));

        $node->appendChild($this->buildClassification(
            $doc,
            $line->income_classification,
            $line->net_amount_cents,
        ));

        return $node;
    }

    private function buildClassification(
        DOMDocument $doc,
        IncomeClassification $classification,
        int $amountCents,
    ): DOMElement {
        $node = $doc->createElementNS(self::NS_INVOICE, 'incomeClassification');

        $node->appendChild($doc->createElementNS(
            self::NS_INCOME,
            'icls:classificationType',
            $classification->classificationType(),
        ));
        $node->appendChild($doc->createElementNS(
            self::NS_INCOME,
            'icls:classificationCategory',
            $classification->classificationCategory(),
        ));
        $node->appendChild($doc->createElementNS(
            self::NS_INCOME,
            'icls:amount',
            $this->amount($amountCents),
        ));

        return $node;
    }

    private function buildSummary(DOMDocument $doc, Invoice $invoice): DOMElement
    {
        $node = $doc->createElementNS(self::NS_INVOICE, 'invoiceSummary');

        $node->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'totalNetValue',
            $this->amount($invoice->net_amount_cents),
        ));
        $node->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'totalVatAmount',
            $this->amount($invoice->vat_amount_cents),
        ));
        $node->appendChild($doc->createElementNS(self::NS_INVOICE, 'totalWithheldAmount', '0.00'));
        $node->appendChild($doc->createElementNS(self::NS_INVOICE, 'totalFeesAmount', '0.00'));
        $node->appendChild($doc->createElementNS(self::NS_INVOICE, 'totalStampDutyAmount', '0.00'));
        $node->appendChild($doc->createElementNS(self::NS_INVOICE, 'totalOtherTaxesAmount', '0.00'));
        $node->appendChild($doc->createElementNS(self::NS_INVOICE, 'totalDeductionsAmount', '0.00'));
        $node->appendChild($doc->createElementNS(
            self::NS_INVOICE,
            'totalGrossValue',
            $this->amount($invoice->total_cents),
        ));

        foreach ($this->groupClassifications($invoice) as $key => $amountCents) {
            $node->appendChild($this->buildClassification(
                $doc,
                IncomeClassification::from($key),
                $amountCents,
            ));
        }

        return $node;
    }

    /**
     * Άθροισμα καθαρής αξίας ανά χαρακτηρισμό.
     *
     * @return array<string, int>
     */
    private function groupClassifications(Invoice $invoice): array
    {
        $totals = [];

        foreach ($invoice->lines as $line) {
            $key = $line->income_classification->value;
            $totals[$key] = ($totals[$key] ?? 0) + $line->net_amount_cents;
        }

        return $totals;
    }
}
