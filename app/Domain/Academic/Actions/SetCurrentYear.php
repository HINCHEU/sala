<?php

namespace App\Domain\Academic\Actions;

use App\Domain\Academic\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

/** Exactly one current year per school. */
class SetCurrentYear
{
    public function handle(AcademicYear $year): void
    {
        DB::transaction(function () use ($year) {
            AcademicYear::where('is_current', true)->whereKeyNot($year->id)->update(['is_current' => false]);
            $year->forceFill(['is_current' => true])->save();

            activity()->performedOn($year)->causedBy(auth()->user())->log('academic.year_set_current');
        });
    }
}
