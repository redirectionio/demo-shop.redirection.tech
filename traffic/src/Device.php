<?php

namespace Traffic;

enum Device: string
{
    case Desktop = 'desktop';
    case Mobile = 'mobile';
    case Tablet = 'tablet';

    /**
     * How many lazy-loaded images the visitor scrolls to, on a page that has enough of them.
     */
    public function lazyImagesSeen(Random $random): int
    {
        return match ($this) {
            self::Desktop => $random->int(4, 12),
            self::Mobile => $random->int(1, 6),
            self::Tablet => $random->int(2, 8),
        };
    }
}
