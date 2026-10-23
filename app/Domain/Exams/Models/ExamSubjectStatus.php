<?php

namespace App\Domain\Exams\Models;

use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\Subject;
use App\Domain\Exams\Enums\SubjectExamStatus;
use App\Models\User;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
