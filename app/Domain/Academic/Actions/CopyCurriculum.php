<?php

namespace App\Domain\Academic\Actions;

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Academic\Models\GradeDivisor;
use Illuminate\Support\Facades\DB;

/** Copies every grade's curriculum and divisors from one year into another, replacing what the target had. */
class CopyCurriculum
{
    public function handle(AcademicYear $from, AcademicYear $to): int
    {
        return DB::transaction(function () use ($from, $to) {
            CurriculumSubject::where('academic_year_id', $to->id)->delete();
            GradeDivisor::where('academic_year_id', $to->id)->delete();

            $copied = 0;
            foreach (CurriculumSubject::where('academic_year_id', $from->id)->get() as $row) {
                $row->replicate()->fill(['academic_year_id' => $to->id])->save();
                $copied++;
            }
            foreach (GradeDivisor::where('academic_year_id', $from->id)->get() as $divisor) {
                $divisor->replicate()->fill(['academic_year_id' => $to->id])->save();
            }

            activity()->performedOn($to)->causedBy(auth()->user())
                ->withProperties(['from_year_id' => $from->id, 'rows' => $copied])
                ->log('academic.curriculum_copied');

            return $copied;
        });
    }
}
