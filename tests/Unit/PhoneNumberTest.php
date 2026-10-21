<?php

use App\Support\PhoneNumber;

it('normalises Cambodian numbers to +855', function (string $input, ?string $expected) {
    expect(PhoneNumber::normalize($input))->toBe($expected);
})->with([
    ['012 345 678', '+85512345678'],
    ['012-345-678', '+85512345678'],
    ['+855 12 345 678', '+85512345678'],
    ['85512345678', '+85512345678'],
    ['+855 012 345 678', '+85512345678'],
    ['097 123 4567', '+855971234567'],
    ['12345', null],
    ['abc', null],
    ['', null],
]);
