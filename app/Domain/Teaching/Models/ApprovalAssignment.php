<?php

namespace App\Domain\Teaching\Models;

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\GradeLevel;
use App\Domain\Teaching\Enums\ApproverType;
use App\Models\User;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
