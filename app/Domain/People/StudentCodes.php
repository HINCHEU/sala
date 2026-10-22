<?php

namespace App\Domain\People;

use App\Domain\People\Models\Student;

/**
 * Next free student codes for the current school: one more than the highest all-digit code, at least 5 digits.
 * Codes a school types itself ("2024-A1") are kept and skipped over. Callers hold a lock on the school row
 * while they use it, so two registrars cannot be handed the same code.
 */
final class StudentCodes
{
    private int $last;

    public function __construct()
    {
        $this->last = (int) Student::withTrashed()->pluck('student_code')
            ->filter(fn (string $code) => ctype_digit($code))
            ->map(fn (string $code) => (int) $code)
            ->max();
    }

    /**
     * Never hand out a code that is about to be used (an import's own codes in later rows).
     *
     * @param  iterable<?string>  $codes
     */
    public function reserve(iterable $codes): self
    {
        foreach ($codes as $code) {
            if ($code !== null && ctype_digit($code)) {
                $this->last = max($this->last, (int) $code);
            }
        }

        return $this;
    }

    public function next(): string
    {
        return str_pad((string) ++$this->last, 5, '0', STR_PAD_LEFT);
    }
}
