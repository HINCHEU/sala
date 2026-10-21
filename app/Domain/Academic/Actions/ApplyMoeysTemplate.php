<?php

namespace App\Domain\Academic\Actions;

use App\Domain\Academic\Enums\GradeStage;
use App\Domain\Academic\Models\GradeLevel;
use App\Domain\Academic\Models\GradingScale;
use App\Domain\Academic\Models\Subject;
use App\Domain\Academic\Models\Track;
use App\Domain\Academic\MoeysTemplate;
use Illuminate\Support\Facades\DB;

/**
 * Adds grades 1–12, the Grade 11–12 tracks, the standard subjects and default mention bands.
 * Only fills what is missing, so running it again never overwrites a school's edits.
 */
class ApplyMoeysTemplate
{
    public function handle(): void
    {
        DB::transaction(function () {
            foreach (range(1, 12) as $number) {
                GradeLevel::firstOrCreate(['number' => $number], MoeysTemplate::grade($number));
            }

            foreach (MoeysTemplate::TRACKS as $track) {
                Track::firstOrCreate(['code' => $track['code']], $track);
            }

            foreach (MoeysTemplate::SUBJECTS as [$code, $km, $en, $category]) {
                Subject::firstOrCreate(['code' => $code], ['name_km' => $km, 'name_en' => $en, 'category' => $category]);
            }

            foreach ([GradeStage::Primary, GradeStage::LowerSecondary, GradeStage::UpperSecondary] as $stage) {
                GradingScale::firstOrCreate(['stage' => $stage], ['bands' => MoeysTemplate::bands($stage)]);
            }

            activity()->causedBy(auth()->user())->log('academic.moeys_template_applied');
        });
    }
}
