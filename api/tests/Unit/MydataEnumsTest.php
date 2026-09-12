<?php

declare(strict_types=1);

use App\Enums\IncomeClassification;
use App\Enums\MydataInvoiceType;
use App\Enums\VatCategory;

it('maps income classifications to aade codes', function () {
    expect(IncomeClassification::GoodsSale->classificationCategory())->toBe('category1_1')
        ->and(IncomeClassification::ServicesProvision->classificationCategory())->toBe('category1_3');
});

it('keeps type and category as a pair', function () {
    // Ο συνδυασμός είναι ζεύγος: κάθε case δίνει και τα δύο.
    foreach (IncomeClassification::cases() as $case) {
        expect($case->classificationType())->toStartWith('E3_')
            ->and($case->classificationCategory())->toStartWith('category');
    }
});

it('maps vat rates to aade categories', function () {
    expect(VatCategory::fromRate(24))->toBe(VatCategory::Rate24)
        ->and(VatCategory::fromRate(13))->toBe(VatCategory::Rate13)
        ->and(VatCategory::fromRate(6))->toBe(VatCategory::Rate6)
        ->and(VatCategory::fromRate(0))->toBe(VatCategory::Zero);
});

it('rejects an unsupported vat rate', function () {
    expect(fn () => VatCategory::fromRate(99))
        ->toThrow(InvalidArgumentException::class);
});

it('has a label for every invoice type', function () {
    foreach (MydataInvoiceType::cases() as $case) {
        expect($case->label())->not->toBeEmpty();
    }
});
