<?php

namespace App\Domain\Exams\Models;

use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\Subject;
use App\Domain\Exams\Enums\SubjectExamStatus;
use App\Models\User;
use App\Support\Tenancy\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $school_id
 * @property int $exam_id
 * @property int $classroom_id
 * @property int $subject_id
 * @property SubjectExamStatus $status
 * @property ?int $submitted_by
 * @property ?Carbon $submitted_at
 * @property ?int $approved_by
 * @property ?Carbon $approved_at
 * @property ?string $returned_reason
 * @property-read ?Exam $exam
 * @property-read ?Classroom $classroom
 * @property-read ?Subject $subject
 * @property-read ?User $submittedBy
 * @property-read ?User $approvedBy
 */
class ExamSubjectStatus extends Model
{
    use BelongsToSchool;

    protected $table = 'exam_subject_status';

    protected $fillable = [
        'exam_id',
        'classroom_id',
        'subject_id',
        'status',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'returned_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubjectExamStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Exam, $this> */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<User, $this> */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
