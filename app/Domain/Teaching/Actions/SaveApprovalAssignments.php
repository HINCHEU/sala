<?php

namespace App\Domain\Teaching\Actions;

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\School\Models\School;
use App\Domain\Teaching\Enums\ApproverType;
use App\Domain\Teaching\Models\ApprovalAssignment;
use Illuminate\Support\Facades\DB;

class SaveApprovalAssignments
{
    /**
     * @param  list<array{grade_level_id: int, approver_type: string, role?: ?string, user_id?: ?int}>  $assignments
     */
    public function handle(School $school, AcademicYear $year, array $assignments): void
    {
        DB::transaction(function () use ($school, $year, $assignments) {
            foreach ($assignments as $item) {
                $type = ApproverType::tryFrom($item['approver_type']) ?? ApproverType::Homeroom;

                ApprovalAssignment::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'academic_year_id' => $year->id,
                        'grade_level_id' => (int) $item['grade_level_id'],
                    ],
                    [
                        'approver_type' => $type,
                        'role' => $type === ApproverType::Role ? ($item['role'] ?? null) : null,
                        'user_id' => $type === ApproverType::User ? (! empty($item['user_id']) ? (int) $item['user_id'] : null) : null,
                    ]
                );
            }

            activity()
                ->performedOn($year)
                ->causedBy(auth()->user())
                ->withProperties(['year_id' => $year->id, 'count' => count($assignments)])
                ->log('approval.assigned');
        });
    }
}
