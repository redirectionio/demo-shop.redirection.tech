<?php

namespace Traffic;

final readonly class UrlMemorySnapshot
{
    public function __construct(
        public UrlMemory $memory,
        /** The commit it was read from, null when nothing was stored yet. */
        public ?string $revision,
    ) {
    }
}
