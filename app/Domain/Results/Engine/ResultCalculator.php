<?php

namespace App\Domain\Results\Engine;

use App\Domain\Exams\Enums\ScoreMark;
use App\Domain\School\Settings\SchoolSettings;
use InvalidArgumentException;

/**
 * Totals and averages for one class in one exam, from plain arrays (no database), so every rule is
 * unit-tested. A cell is a score, ScoreMark::Absent, ScoreMark::Exempt, or null when not entered yet
 * (only while an exam is open; counted as 0 and reported as missing).
 *
 * - Absent counts 0. With absentSubjectPolicy "exclude_from_rank" the student keeps an average but no rank.
 * - Exempt subjects are left out: of the total, and of what the average is measured against
 *   (the divisor shrinks in proportion to the max scores left out).
 * - The average is rounded once, half up to two decimals, and the pass line is the same average
 *   computed from the curriculum's pass scores.
 */
final class ResultCalculator
{
    public function __construct(private SchoolSettings $settings) {}

    /**
     * @param  array<int, array<int, float|ScoreMark|null>>  $scores  enrollment id => [subject id => cell]
     * @return array<int, array{total: float, average: float, absent: int, exempt: int, missing: int, rankable: bool, passed: bool}>
     */
    public function calculate(array $scores, Curriculum $curriculum): array
    {
        $method = $this->settings->averageMethod;
        if ($method === 'divisor' && ! $curriculum->divisor) {
            throw new InvalidArgumentException('The divisor method needs a divisor for this grade.');
        }

        $out = [];
        foreach ($scores as $enrollmentId => $cells) {
            $taken = $passes = [];
            $absent = $exempt = $missing = 0;
            foreach ($curriculum->subjects as $subjectId => $rule) {
                $cell = $cells[$subjectId] ?? null;
                if ($cell === ScoreMark::Exempt) {
                    $exempt++;

                    continue;
                }
                $absent += $cell === ScoreMark::Absent ? 1 : 0;
                $missing += $cell === null ? 1 : 0;
                $taken[$subjectId] = is_float($cell) ? $cell : 0.0;
                $passes[$subjectId] = $rule->pass;
            }

            $total = array_sum($taken);
            $average = Round::halfUp($this->average($taken, $curriculum, $method));
            $passLine = Round::halfUp($this->average($passes, $curriculum, $method));

            $out[$enrollmentId] = [
                'total' => Round::halfUp($total),
                'average' => $average,
                'absent' => $absent,
                'exempt' => $exempt,
                'missing' => $missing,
                'rankable' => $taken !== [] && ! ($absent > 0 && $this->settings->absentSubjectPolicy === 'exclude_from_rank'),
                'passed' => $taken !== [] && $average >= $passLine,
            ];
        }

        return $out;
    }

    /** @param array<int, float> $taken subject id => score, exempt subjects already left out */
    private function average(array $taken, Curriculum $curriculum, string $method): float
    {
        if ($taken === []) {
            return 0.0;
        }

        return match ($method) {
            'divisor' => array_sum($taken) / $this->divisor($taken, $curriculum),
            'weighted' => $this->weighted($taken, $curriculum),
            'simple' => array_sum($taken) / count($taken),
            default => throw new InvalidArgumentException("Unknown average method {$method}."),
        };
    }

    /**
     * The divisor, shrunk by the share of max score an exempt subject removes.
     *
     * @param  array<int, float>  $taken
     */
    private function divisor(array $taken, Curriculum $curriculum): float
    {
        $allMax = array_sum(array_map(fn (SubjectRule $r) => $r->max, $curriculum->subjects));
        $takenMax = array_sum(array_map(fn (int $id) => $curriculum->subjects[$id]->max, array_keys($taken)));

        return (float) $curriculum->divisor * ($allMax > 0 ? $takenMax / $allMax : 1);
    }

    /**
     * Σ(score ÷ max × 10 × coefficient) ÷ Σ coefficient: an average out of 10.
     *
     * @param  array<int, float>  $taken
     */
    private function weighted(array $taken, Curriculum $curriculum): float
    {
        $sum = $weights = 0.0;
        foreach ($taken as $id => $score) {
            $rule = $curriculum->subjects[$id];
            $sum += $rule->max > 0 ? $score / $rule->max * 10 * $rule->coefficient : 0;
            $weights += $rule->coefficient;
        }

        return $weights > 0 ? $sum / $weights : 0.0;
    }
}
