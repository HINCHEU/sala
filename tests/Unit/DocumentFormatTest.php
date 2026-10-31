<?php

use App\Support\Pdf\DocumentFormat;

it('prints numbers as typed, or in Khmer digits when the school asks', function () {
    $latin = new DocumentFormat;
    $khmer = new DocumentFormat(khmerNumerals: true);

    expect($latin->cell(75.0))->toBe('75')
        ->and($latin->cell(7.5))->toBe('7.5')
        ->and($latin->cell(100.0))->toBe('100')
        ->and($latin->decimal(25))->toBe('25.00')
        ->and($khmer->cell(7.5))->toBe('៧.៥')
        ->and($khmer->decimal('25.10'))->toBe('២៥.១០')
        ->and($khmer->int(12))->toBe('១២');
});

it('leaves the letters and blanks alone', function () {
    $khmer = new DocumentFormat(khmerNumerals: true);

    expect($khmer->cell('A'))->toBe('A')
        ->and($khmer->cell(null))->toBe('—')
        ->and($khmer->decimal(null))->toBe('—')
        ->and($khmer->int(null))->toBe('—');
});
