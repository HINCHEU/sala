<?php

use App\Domain\Academic\Enums\GradeStage;
use App\Domain\Academic\MoeysTemplate;
use App\Domain\Exams\Enums\ScoreMark;
use App\Domain\Results\Engine\Curriculum;
use App\Domain\Results\Engine\MentionResolver;
use App\Domain\Results\Engine\Ranker;
use App\Domain\Results\Engine\ResultCalculator;
use App\Domain\Results\Engine\Round;
use App\Domain\Results\Engine\SubjectRule;
use App\Domain\School\Settings\SchoolSettings;

/** Khmer /100 (coef 2), Maths /50, History /50; divisor 4 makes the average out of 50. */
function lowerSecondary(?float $divisor = 4): Curriculum
{
    return new Curriculum([
        new SubjectRule(1, 100, 2, 50),
        new SubjectRule(2, 50, 1, 25),
        new SubjectRule(3, 50, 1, 25),
    ], $divisor);
}

function calc(array $scores, string $method = 'divisor', string $absent = 'zero', ?Curriculum $c = null): array
{
    return (new ResultCalculator(new SchoolSettings(averageMethod: $method, absentSubjectPolicy: $absent)))
        ->calculate($scores, $c ?? lowerSecondary());
}

it('adds the scores and divides by the divisor', function () {
    $r = calc([10 => [1 => 80.0, 2 => 40.0, 3 => 30.5]]);

    expect($r[10])->toMatchArray(['total' => 150.5, 'average' => 37.63, 'absent' => 0, 'passed' => true, 'rankable' => true]);
});

it('rounds once, half up, at the end', function () {
    // 150.98 / 4 = 37.745 exactly: half up gives 37.75 (float noise would give 37.74).
    expect(calc([1 => [1 => 80.98, 2 => 40.0, 3 => 30.0]])[1]['average'])->toBe(37.75)
        ->and(Round::halfUp(12.345))->toBe(12.35)
        ->and(Round::halfUp(12.344999))->toBe(12.34)
        ->and(Round::halfUp(0.005))->toBe(0.01);
});

it('averages by weight out of 10, or as a plain mean', function () {
    $cells = [1 => [1 => 80.0, 2 => 40.0, 3 => 30.0]];

    // (8 × 2 + 8 × 1 + 6 × 1) / 4 = 7.5
    expect(calc($cells, 'weighted')[1]['average'])->toBe(7.5)
        ->and(calc($cells, 'simple')[1]['average'])->toBe(50.0);
});

it('counts absent as 0, and can keep an absent student out of the ranking', function () {
    $cells = [1 => [1 => 80.0, 2 => ScoreMark::Absent, 3 => 30.0]];

    expect(calc($cells)[1])->toMatchArray(['total' => 110.0, 'average' => 27.5, 'absent' => 1, 'rankable' => true])
        ->and(calc($cells, absent: 'exclude_from_rank')[1])->toMatchArray(['average' => 27.5, 'rankable' => false]);
});

it('gives an all-absent student 0 and a fail', function () {
    $a = ScoreMark::Absent;

    expect(calc([1 => [1 => $a, 2 => $a, 3 => $a]])[1])->toMatchArray(['total' => 0.0, 'average' => 0.0, 'absent' => 3, 'passed' => false]);
});

it('leaves an exempt subject out of the total and shrinks the divisor in proportion', function () {
    // History (50 of 200 max) exempt: divisor 4 × 150/200 = 3; (80 + 40) / 3 = 40
    expect(calc([1 => [1 => 80.0, 2 => 40.0, 3 => ScoreMark::Exempt]])[1])->toMatchArray(['total' => 120.0, 'average' => 40.0, 'exempt' => 1]);
});

it('reports cells not entered yet as missing and counts them as 0', function () {
    expect(calc([1 => [1 => 80.0]])[1])->toMatchArray(['total' => 80.0, 'missing' => 2, 'average' => 20.0]);
});

it('passes on the pass line made the same way from the pass scores', function () {
    // pass scores 50 + 25 + 25 = 100, / 4 = 25.00. Compared as printed: 99.99 / 4 = 24.9975 prints 25.00.
    expect(calc([1 => [1 => 50.0, 2 => 25.0, 3 => 25.0]])[1]['passed'])->toBeTrue()
        ->and(calc([1 => [1 => 50.0, 2 => 25.0, 3 => 24.99]])[1]['passed'])->toBeTrue()
        ->and(calc([1 => [1 => 50.0, 2 => 25.0, 3 => 24.9]])[1]['passed'])->toBeFalse(); // 24.975 prints 24.98
});

it('refuses the divisor method without a divisor', function () {
    calc([1 => [1 => 1.0]], c: lowerSecondary(null));
})->throws(InvalidArgumentException::class);

it('ranks with ties sharing a place and the next place skipped', function () {
    expect((new Ranker)->rank([1 => 40.0, 2 => 45.5, 3 => 40.0, 4 => 38.0, 5 => null]))
        ->toBe([1 => 2, 2 => 1, 3 => 2, 4 => 4, 5 => null]);
});

it('ranks a one-student class first', function () {
    expect((new Ranker)->rank([7 => 12.0]))->toBe([7 => 1]);
});

it('ranks across a grade with classes of different sizes as one list', function () {
    $sevenA = [1 => 41.0, 2 => 39.5];
    $sevenB = [3 => 44.0, 4 => 39.5, 5 => 20.0];

    expect((new Ranker)->rank($sevenA + $sevenB))->toBe([1 => 2, 2 => 3, 3 => 1, 4 => 3, 5 => 5]);
});

it('treats averages equal to two decimals as a tie', function () {
    expect((new Ranker)->rank([1 => 37.745 + 1e-12, 2 => 37.745]))->toBe([1 => 1, 2 => 1]);
});

it('finds the mention band, with the top band taking a perfect score', function () {
    $resolver = new MentionResolver(MoeysTemplate::bands(GradeStage::LowerSecondary));

    expect($resolver->resolve(50.0)['mention'])->toBe('excellent')
        ->and($resolver->resolve(45.0)['mention'])->toBe('excellent')
        ->and($resolver->resolve(44.99)['mention'])->toBe('very_good')
        ->and($resolver->resolve(25.0)['mention'])->toBe('average')
        ->and($resolver->resolve(0.0)['mention'])->toBe('weak')
        ->and($resolver->resolve(37.63))->toMatchArray(['mention' => 'good', 'mention_km' => 'ល្អ', 'letter' => 'C']);
});
