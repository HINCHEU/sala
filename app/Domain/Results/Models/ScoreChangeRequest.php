<?php

namespace App\Domain\Results\Models;

use App\Domain\Academic\Models\Subject;
use App\Domain\Exams\Models\Exam;
use App\Domain\People\Models\Enrollment;
use App\Domain\Results\Enums\ChangeRequestStatus;
use App\Models\User;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A correction to a locked exam's score: a teacher asks with a reason, the director decides, and an
 * approved change is applied and the results recalculated as a new version.
 *
 * @property int $exam_id
 * @property int $enrollment_id
 * @property int $subject_id
 * @property ?string $old_value
 * @property string $new_value
 * @property string $reason
 * @property ChangeRequestStatus $status
 * @property-read ?Exam $exam
 * @property-read ?Enrollment $enrollment
 * @property-read ?Subject $subject
 * @property-read ?User $requester
 * @property-read ?User $decider
 */
class ScoreChangeRequest extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'exam_id', 'enrollment_id', 'subject_id', 'old_value', 'new_value', 'reason', 'status',
        'requested_by', 'decided_by', 'decided_at', 'decision_note',
    ];

    protected function casts(): array
    {
        return ['status' => ChangeRequestStatus::class, 'decided_at' => 'datetime'];
    }

    /** @return BelongsTo<Exam, $this> */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /** @return BelongsTo<Enrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
