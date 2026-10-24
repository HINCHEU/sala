<?php

namespace App\Domain\Results\Engine;

/**
 * The mention (និទ្ទេស) band an average falls in. Bands run from min up to, but not including, max;
 * the top band also takes its max (a perfect score), and anything above it.
 */
final class MentionResolver
{
    /** @param list<array{min: float|int|string, max: float|int|string, mention: string, mention_km: string, mention_en: string, letter: string}> $bands */
    public function __construct(private array $bands) {}

    /** @return ?array{mention: string, mention_km: string, mention_en: string, letter: string} */
    public function resolve(float $average): ?array
    {
        $bands = $this->bands;
        usort($bands, fn ($a, $b) => (float) $b['min'] <=> (float) $a['min']);

        foreach ($bands as $i => $band) {
            $inTop = $i === 0 && $average >= (float) $band['min'];
            if ($inTop || ($average >= (float) $band['min'] && $average < (float) $band['max'])) {
                return array_intersect_key($band, array_flip(['mention', 'mention_km', 'mention_en', 'letter']));
            }
        }

        return null;
    }
}
