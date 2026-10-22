<?php

namespace App\Domain\People\Models;

use App\Domain\Academic\Models\Subject;
use App\Domain\People\Enums\EmploymentType;
use App\Domain\People\Enums\Gender;
use App\Domain\People\Enums\TeacherStatus;
use App\Models\User;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A teacher's profile. The login (user_id) is optional and comes from an invitation.
 *
 * @property Gender $gender
 * @property EmploymentType $employment_type
 * @property TeacherStatus $status
 * @property ?Carbon $dob
 * @property ?Carbon $hired_at
 */
class Teacher extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'staff_code', 'name_km', 'name_en', 'gender', 'dob', 'phone', 'email', 'qualification',
        'employment_type', 'hired_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'employment_type' => EmploymentType::class,
            'status' => TeacherStatus::class,
            'dob' => 'date:Y-m-d',
            'hired_at' => 'date:Y-m-d',
        ];
    }

    /**
     * What they can teach (M4's assignments say what they do teach).
     *
     * @return BelongsToMany<Subject, $this>
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'teacher_subject');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
