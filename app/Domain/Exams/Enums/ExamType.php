<?php

namespace App\Domain\Exams\Enums;

enum ExamType: string
{
    case Monthly = 'monthly';
    case Semester = 'semester';
    case Final = 'final';
    case Quiz = 'quiz';

    public function label(): string
    {
        return __('exams.types.'.$this->value);
    }
}
