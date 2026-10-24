<?php

namespace App\Domain\Results\Engine;

final class Ranker
{
    /**
     * Competition ranking on already-rounded averages: 1, 2, 2, 4. A null average (not rankable) gets
     * no rank and takes no place. Averages are compared as printed (two decimals), so the rank always
     * agrees with the sheet.
     *
     * @param  array<int, ?float>  $averages  id => average
     * @return array<int, ?int>
     */
    public function rank(array $averages): array
    {
        $ranks = array_map(fn () => null, $averages);
        $ranked = array_filter($averages, fn (?float $a) => $a !== null);
        uasort($ranked, fn (float $a, float $b) => $b <=> $a);

        $position = 0;
        $rank = 0;
        $previous = null;
        foreach ($ranked as $id => $average) {
            $position++;
            $printed = number_format($average, 2, '.', '');
            if ($printed !== $previous) {
                $rank = $position;
                $previous = $printed;
            }
            $ranks[$id] = $rank;
        }

        return $ranks;
    }
}
