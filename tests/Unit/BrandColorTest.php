<?php

use App\Domain\School\BrandColor;

it('accepts Sala primary and rejects light colours', function () {
    expect(BrandColor::isReadable('#1f4fbf'))->toBeTrue()
        ->and(BrandColor::isReadable('#e9b949'))->toBeFalse()
        ->and(BrandColor::isReadable('#ffffff'))->toBeFalse();
});

it('suggests a darker shade that passes', function () {
    $suggestion = BrandColor::suggestDarker('#e9b949');

    expect($suggestion)->toMatch('/^#[0-9a-f]{6}$/')
        ->and(BrandColor::isReadable($suggestion))->toBeTrue();
});

it('mixes the soft tint toward white', function () {
    expect(BrandColor::soft('#1f4fbf'))->toBe('#e4eaf7');
});
