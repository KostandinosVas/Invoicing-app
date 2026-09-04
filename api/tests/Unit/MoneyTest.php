<?php

declare(strict_types=1);

use App\ValueObjects\Money;

it('stores and returns cents', function () {
    expect(Money::fromCents(1250)->cents())->toBe(1250);
});

it('adds two amounts', function () {
    $sum = Money::fromCents(1250)->plus(Money::fromCents(750));

    expect($sum->cents())->toBe(2000);
});

it('calculates vat at 24 percent', function () {
    $vat = Money::fromCents(50000)->percentage(24);

    expect($vat->cents())->toBe(12000);
});

it('rounds half up', function () {
    // 333 λεπτά × 24% = 79,92 → 80
    $vat = Money::fromCents(333)->percentage(24);

    expect($vat->cents())->toBe(80);
});

it('does not lose precision like floats', function () {
    $sum = Money::fromCents(10)
        ->plus(Money::fromCents(20));

    expect($sum->cents())->toBe(30);
    expect(0.1 + 0.2)->not->toBe(0.3);
});

it('is immutable', function () {
    $original = Money::fromCents(1000);
    $original->plus(Money::fromCents(500));

    expect($original->cents())->toBe(1000);
});
