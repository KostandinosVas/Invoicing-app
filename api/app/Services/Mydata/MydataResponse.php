<?php

declare(strict_types=1);

namespace App\Services\Mydata;

final readonly class MydataResponse
{
    /**
     * @param  list<string>  $errors
     */
    private function __construct(
        public bool $accepted,
        public ?string $mark,
        public ?string $uid,
        public ?string $authenticationCode,
        public array $errors,
    ) {}

    /**
     * @param  list<string>  $errors
     */
    public static function rejected(array $errors): self
    {
        return new self(false, null, null, null, $errors);
    }

    public static function accepted(
        string $mark,
        ?string $uid,
        ?string $authenticationCode,
    ): self {
        return new self(true, $mark, $uid, $authenticationCode, []);
    }
}
