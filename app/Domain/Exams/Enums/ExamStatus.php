<?php

namespace App\Domain\Exams\Enums;

enum ExamStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Approved = 'approved';
    case Locked = 'locked';
    case Published = 'published';

    public function label(): string
    {
        return __('exams.statuses.'.$this->value);
    }

    public function canTransitionTo(ExamStatus $target): bool
    {
        return match ($this) {
            self::Draft => in_array($target, [self::Open], true),
            self::Open => in_array($target, [self::Draft, self::Approved, self::Locked], true),
            self::Approved => in_array($target, [self::Open, self::Locked], true),
            self::Locked => in_array($target, [self::Published], true),
            self::Published => false,
        };
    }
}
