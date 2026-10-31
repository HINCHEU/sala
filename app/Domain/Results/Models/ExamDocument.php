<?php

namespace App\Domain\Results\Models;

use App\Domain\Academic\Models\Classroom;
use App\Domain\Exams\Models\Exam;
use App\Domain\Results\Enums\DocumentKind;
use App\Domain\Results\Enums\DocumentStatus;
use App\Support\Files\StoredFile;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * A PDF generated for one class in one exam, for one results version.
 *
 * @property int $exam_id
 * @property int $classroom_id
 * @property int $version
 * @property DocumentKind $kind
 * @property string $paper
 * @property DocumentStatus $status
 * @property-read ?StoredFile $file
 */
class ExamDocument extends Model
{
    use BelongsToSchool;

    public const COLLECTION = 'exam-documents';

    protected $fillable = ['exam_id', 'classroom_id', 'version', 'kind', 'paper', 'status', 'requested_by'];

    protected function casts(): array
    {
        return ['kind' => DocumentKind::class, 'status' => DocumentStatus::class];
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

    /** @return MorphOne<StoredFile, $this> */
    public function file(): MorphOne
    {
        return $this->morphOne(StoredFile::class, 'owner')->where('collection', self::COLLECTION);
    }
}
