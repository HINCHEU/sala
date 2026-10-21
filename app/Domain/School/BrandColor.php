<?php

namespace App\Domain\School;

/**
 * A school's brand colour replaces Sala's `primary`, with white (`on-primary`) text on it,
 * so it must reach 4.5:1 contrast against white.
 */
class BrandColor
{
    public const MIN_CONTRAST = 4.5;

    public static function contrastWithWhite(string $hex): float
    {
        return 1.05 / (self::luminance($hex) + 0.05);
    }

    public static function isReadable(string $hex): bool
    {
        return self::contrastWithWhite($hex) >= self::MIN_CONTRAST;
    }

    /** Darken step by step until white text reads on it. */
    public static function suggestDarker(string $hex): string
    {
        [$r, $g, $b] = self::rgb($hex);

        while (! self::isReadable(self::hex($r, $g, $b))) {
            [$r, $g, $b] = [(int) ($r * 0.92), (int) ($g * 0.92), (int) ($b * 0.92)];
        }

        return self::hex($r, $g, $b);
    }

    /** The `primary-soft` tint: the colour mixed with about 88% white. */
    public static function soft(string $hex): string
    {
        [$r, $g, $b] = array_map(fn (int $c) => (int) round($c + (255 - $c) * 0.88), self::rgb($hex));

        return self::hex($r, $g, $b);
    }

    private static function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(function (int $c) {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hex));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /** @return array{int, int, int} */
    private static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [(int) hexdec(substr($hex, 0, 2)), (int) hexdec(substr($hex, 2, 2)), (int) hexdec(substr($hex, 4, 2))];
    }

    private static function hex(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
