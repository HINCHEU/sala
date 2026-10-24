<?php

namespace App\Domain\Results\Engine;

/** One curriculum subject as the engine sees it: max score, coefficient and pass score. */
final readonly class SubjectRule
{
    public function __construct(
        public int $subjectId,
        public float $max,
        public float $coefficient,
        public float $pass,
    ) {}
}
