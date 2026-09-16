<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;

final class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return in_array($invoice->company_id, $user->accessibleCompanyIds(), true);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice)
            && $invoice->status === InvoiceStatus::Draft;
    }

    public function issue(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice)
            && $invoice->status === InvoiceStatus::Draft;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice)
            && $invoice->status === InvoiceStatus::Draft;
    }

    public function submit(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice)
            && in_array($invoice->status, [
                InvoiceStatus::Issued,
                InvoiceStatus::Rejected,
            ], true);
    }

    public function createCreditNote(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice)
            && $invoice->document_type === 'invoice'
            && $invoice->status !== InvoiceStatus::Draft
            && $invoice->status !== InvoiceStatus::Cancelled;
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice)
            && $invoice->status === InvoiceStatus::Submitted
            && $invoice->mydata_mark !== null;
    }

    public function email(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice) && $invoice->number !== null;
    }
}
