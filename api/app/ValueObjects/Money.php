<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Brick\Math\RoundingMode;
use Brick\Money\Money as BrickMoney;

final readonly class Money
{
    private function __construct(
        private BrickMoney $amount,
    ) {}

    public static function fromCents(int $cents): self
    {
        return new self(BrickMoney::ofMinor($cents, 'EUR'));
    }

    public function cents(): int
    {
        return $this->amount->getMinorAmount()->toInt();
    }

    public function plus(self $other): self
    {
        return new self($this->amount->plus($other->amount));
    }

    public function multipliedBy(string $factor): self
    {
        return new self(
            $this->amount->multipliedBy($factor, RoundingMode::HalfUp)
        );
    }

    public function percentage(int $rate): self
    {
        return $this->multipliedBy((string) ($rate / 100));
    }

    public function format(): string
    {
        return $this->amount->formatToLocale('el_GR');
    }
}
