<?php

namespace App\Domain\Academic\Models;

use App\Domain\Academic\Enums\GradeStage;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Mention bands for one stage, best first: [{min, max, mention, mention_km, mention_en, letter}].
 *
 * @property GradeStage $stage
 * @property list<array{min: float, max: float, mention: string, mention_km: string, mention_en: string, letter: string}> $bands
 */
class GradingScale extends Model
{
    use BelongsToSchool, LogsActivity;

    protected $fillable = ['stage', 'bands'];

    protected function casts(): array
    {
        return ['stage' => GradeStage::class, 'bands' => 'array'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
