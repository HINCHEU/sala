<?php

namespace App\Domain\Results\Engine;

/** A class's subjects and its divisor (មេចែក), the inputs to one average. */
final readonly class Curriculum
{
    /** @var array<int, SubjectRule> keyed by subject id */
    public array $subjects;

    /** @param list<SubjectRule> $subjects */
    public function __construct(array $subjects, public ?float $divisor = null)
    {
        $keyed = [];
        foreach ($subjects as $subject) {
            $keyed[$subject->subjectId] = $subject;
        }
        $this->subjects = $keyed;
    }
}
