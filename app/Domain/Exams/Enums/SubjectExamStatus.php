<?php

namespace App\Domain\Exams\Enums;

enum SubjectExamStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Returned = 'returned';

    public function label(): string
    {
        return __('exams.subject_statuses.'.$this->value);
    }

    public function canTransitionTo(SubjectExamStatus $target): bool
    {
        return match ($this) {
            self::Draft => in_array($target, [self::Submitted], true),
            self::Submitted => in_array($target, [self::Approved, self::Returned, self::Draft], true),
            self::Approved => in_array($target, [self::Submitted], true),
            self::Returned => in_array($target, [self::Draft, self::Submitted], true),
        };
    }
}
