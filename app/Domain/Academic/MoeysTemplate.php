<?php

namespace App\Domain\Academic;

use App\Domain\Academic\Enums\GradeStage;
use App\Domain\Academic\Enums\Mention;
use App\Domain\Academic\Enums\SubjectCategory;

/**
 * Starting data from general MoEYS practice. Every school edits it afterwards; confirm the subject
 * list and mention bands with the pilot school before launch (architecture plan, Cambodia section).
 */
final class MoeysTemplate
{
    /** Grades 11–12 split into Science and Social Science. */
    public const TRACK_GRADES = [11, 12];

    /** @var list<array{code: string, name_km: string, name_en: string}> */
    public const TRACKS = [
        ['code' => 'SCI', 'name_km' => 'ថ្នាក់វិទ្យាសាស្ត្រ', 'name_en' => 'Science'],
        ['code' => 'SOC', 'name_km' => 'ថ្នាក់វិទ្យាសាស្ត្រសង្គម', 'name_en' => 'Social Science'],
    ];

    /** @var list<array{0: string, 1: string, 2: string, 3: SubjectCategory}> code, Khmer, English, category */
    public const SUBJECTS = [
        ['KHM', 'ភាសាខ្មែរ', 'Khmer', SubjectCategory::Language],
        ['MATH', 'គណិតវិទ្យា', 'Mathematics', SubjectCategory::Science],
        ['SCI', 'វិទ្យាសាស្ត្រ', 'Science', SubjectCategory::Science],
        ['SOCST', 'សិក្សាសង្គម', 'Social Studies', SubjectCategory::Social],
        ['PHY', 'រូបវិទ្យា', 'Physics', SubjectCategory::Science],
        ['CHEM', 'គីមីវិទ្យា', 'Chemistry', SubjectCategory::Science],
        ['BIO', 'ជីវវិទ្យា', 'Biology', SubjectCategory::Science],
        ['EARTH', 'ផែនដីវិទ្យា', 'Earth Science', SubjectCategory::Science],
        ['HIST', 'ប្រវត្តិវិទ្យា', 'History', SubjectCategory::Social],
        ['GEO', 'ភូមិវិទ្យា', 'Geography', SubjectCategory::Social],
        ['MORAL', 'សីលធម៌-ពលរដ្ឋវិជ្ជា', 'Morality and Civics', SubjectCategory::Social],
        ['ENG', 'ភាសាអង់គ្លេស', 'English', SubjectCategory::Language],
        ['FRA', 'ភាសាបារាំង', 'French', SubjectCategory::Language],
        ['ICT', 'បច្ចេកវិទ្យាព័ត៌មាន', 'ICT', SubjectCategory::Other],
        ['HOME', 'គេហវិទ្យា', 'Home Economics', SubjectCategory::Other],
        ['PE', 'អប់រំកាយ និងកីឡា', 'Physical Education and Sport', SubjectCategory::Other],
        ['ART', 'សិល្បៈ', 'Arts', SubjectCategory::Other],
        ['LIFE', 'បំណិនជីវិត', 'Life Skills', SubjectCategory::Other],
    ];

    public const KHMER_DIGITS = ['០', '១', '២', '៣', '៤', '៥', '៦', '៧', '៨', '៩'];

    /** @return array{number: int, name_km: string, name_en: string, stage: GradeStage, has_tracks: bool} */
    public static function grade(int $number): array
    {
        return [
            'number' => $number,
            'name_km' => 'ថ្នាក់ទី'.strtr((string) $number, self::KHMER_DIGITS),
            'name_en' => "Grade {$number}",
            'stage' => GradeStage::forGrade($number),
            'has_tracks' => in_array($number, self::TRACK_GRADES, true),
        ];
    }

    /**
     * Mention bands for a stage. Primary averages are out of 10; secondary averages (total ÷ divisor) out of 50.
     *
     * @return list<array{min: float, max: float, mention: string, mention_km: string, mention_en: string, letter: string}>
     */
    public static function bands(GradeStage $stage): array
    {
        $top = $stage === GradeStage::Primary ? 10 : 50;
        $step = $top / 10;

        $rows = [
            [Mention::Excellent, 'ល្អប្រសើរ', 'Excellent', 'A', 9],
            [Mention::VeryGood, 'ល្អណាស់', 'Very good', 'B', 8],
            [Mention::Good, 'ល្អ', 'Good', 'C', 7],
            [Mention::FairlyGood, 'ល្អបង្គួរ', 'Fairly good', 'D', 6],
            [Mention::Average, 'មធ្យម', 'Average', 'E', 5],
            [Mention::Weak, 'ខ្សោយ', 'Weak', 'F', 0],
        ];

        $bands = [];
        $max = (float) $top;
        foreach ($rows as [$mention, $km, $en, $letter, $from]) {
            $min = (float) ($from * $step);
            $bands[] = ['min' => $min, 'max' => $max, 'mention' => $mention->value, 'mention_km' => $km, 'mention_en' => $en, 'letter' => $letter];
            $max = $min;
        }

        return $bands;
    }
}
