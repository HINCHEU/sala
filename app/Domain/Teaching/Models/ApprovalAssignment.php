<?php

namespace App\Domain\Teaching\Models;

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\GradeLevel;
use App\Domain\Teaching\Enums\ApproverType;
use App\Models\User;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $school_id
 * @property int $academic_year_id
 * @property ?int $grade_level_id
 * @property ApproverType $approver_type
 * @property ?string $role
 * @property ?int $user_id
 * @property-read ?AcademicYear $academicYear
 * @property-read ?GradeLevel $gradeLevel
 * @property-read ?User $user
 */
class ApprovalAssignment extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'academic_year_id',
        'grade_level_id',
        'approver_type',
        'role',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'approver_type' => ApproverType::class,
        ];
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return BelongsTo<GradeLevel, $this> */
    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
