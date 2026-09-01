<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;

it('allows draft to be issued', function () {
    expect(InvoiceStatus::Draft->canTransitionTo(InvoiceStatus::Issued))->toBeTrue();
});

it('does not allow draft to be submitted directly', function () {
    expect(InvoiceStatus::Draft->canTransitionTo(InvoiceStatus::Submitted))->toBeFalse();
});

it('allows a rejected invoice to be retried', function () {
    expect(InvoiceStatus::Rejected->canTransitionTo(InvoiceStatus::Submitting))->toBeTrue();
});

it('does not allow anything after cancellation', function () {
    expect(InvoiceStatus::Cancelled->isFinal())->toBeTrue();

    foreach (InvoiceStatus::cases() as $status) {
        expect(InvoiceStatus::Cancelled->canTransitionTo($status))->toBeFalse();
    }
});

it('does not allow an issued invoice to go back to draft', function () {
    expect(InvoiceStatus::Issued->canTransitionTo(InvoiceStatus::Draft))->toBeFalse();
});
