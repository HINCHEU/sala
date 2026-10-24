<?php

/**
 * A monthly score sheet the way the school prints it, used by GoldenSheetTest.
 *
 * Placeholder: worked out by hand in the MoEYS format (subject total ÷ divisor = average out of 50,
 * competition ranking, mention bands from the default template). Replace this file with the pilot
 * school's real, anonymised paper sheet — the test then proves the system matches it to the last digit
 * (architecture plan, M6 "Accepted when").
 *
 * scores: subject code => score, or 'A' (absent) / 'E' (exempt).
 * expect: [total, average, class rank (null = not ranked), mention key, passed]
 */
return [
    'divisor' => 3,
    'subjects' => [
        ['code' => 'KHM', 'max' => 100, 'pass' => 50],
        ['code' => 'MATH', 'max' => 50, 'pass' => 25],
    ],
    'students' => [
        ['name' => ['កែវ', 'មុនី'], 'scores' => ['KHM' => 90, 'MATH' => 45], 'expect' => [135.00, 45.00, 1, 'excellent', true]],
        ['name' => ['ចាន់', 'សុភា'], 'scores' => ['KHM' => 70, 'MATH' => 35], 'expect' => [105.00, 35.00, 2, 'good', true]],
        ['name' => ['ពៅ', 'វណ្ណា'], 'scores' => ['KHM' => 70, 'MATH' => 'A'], 'expect' => [70.00, 23.33, 5, 'weak', false]],
        ['name' => ['សុខ', 'ដារ៉ា'], 'scores' => ['KHM' => 80.5, 'MATH' => 24.5], 'expect' => [105.00, 35.00, 2, 'good', true]],
        ['name' => ['ហេង', 'លីណា'], 'scores' => ['KHM' => 74.98, 'MATH' => 0], 'expect' => [74.98, 24.99, 4, 'weak', false]],
    ],
];
