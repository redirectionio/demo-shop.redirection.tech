<?php

namespace Traffic;

final readonly class HumanAgent
{
    private function __construct(
        public string $userAgent,
        /** Safari asks for these icons, the site has none: they are among the most common 404 errors. */
        public bool $requestsAppleTouchIcons,
    ) {
    }

    public static function safari(string $userAgent): self
    {
        return new self($userAgent, true);
    }

    public static function other(string $userAgent): self
    {
        return new self($userAgent, false);
    }
}
