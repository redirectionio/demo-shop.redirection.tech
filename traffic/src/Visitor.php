<?php

namespace Traffic;

final readonly class Visitor
{
    public function __construct(
        /** What kind of visitor it is, like "human" or "Googlebot". */
        public string $kind,
        public int $number,
        public string $userAgent,
        public ?string $acceptLanguage = null,
    ) {
    }

    public function label(): string
    {
        return "{$this->kind}#{$this->number}";
    }
}
