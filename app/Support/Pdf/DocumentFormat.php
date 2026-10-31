<?php

namespace App\Support\Pdf;

use Carbon\CarbonInterface;

/** How numbers and dates print on a school's documents. */
final class DocumentFormat
{
    private const KHMER_MONTHS = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];

    public function __construct(private bool $khmerNumerals = false) {}

    public static function khmerDigits(string $text): string
    {
        return strtr($text, ['០', '១', '២', '៣', '៤', '៥', '៦', '៧', '៨', '៩']);
    }

    /** A count, rank or roll number. */
    public function int(?int $value): string
    {
        return $value === null ? '—' : $this->digits((string) $value);
    }

    /** A total or average: always two decimals, as on the result sheet. */
    public function decimal(float|string|null $value): string
    {
        return $value === null ? '—' : $this->digits(number_format((float) $value, 2, '.', ''));
    }

    /** A score cell: the score as it was typed (75, 7.5), the letter A / E, or a dash when blank. */
    public function cell(float|string|null $cell): string
    {
        if ($cell === null) {
            return '—';
        }

        return is_string($cell) ? $cell : $this->digits(rtrim(rtrim(number_format($cell, 2, '.', ''), '0'), '.'));
    }

    /** Gregorian date: ថ្ងៃទី៣១ ខែតុលា ឆ្នាំ២០២៦. Khmer dates are always in Khmer digits. */
    public function date(?CarbonInterface $date): string
    {
        if ($date === null) {
            return '—';
        }
        if (app()->getLocale() !== 'km') {
            return $date->format('j F Y');
        }

        return sprintf(
            'ថ្ងៃទី%s ខែ%s ឆ្នាំ%s',
            self::khmerDigits($date->format('d')), self::KHMER_MONTHS[$date->month - 1], self::khmerDigits($date->format('Y')),
        );
    }

    private function digits(string $text): string
    {
        return $this->khmerNumerals ? self::khmerDigits($text) : $text;
    }
}
