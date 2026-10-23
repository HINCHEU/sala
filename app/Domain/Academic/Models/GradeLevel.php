<?php

namespace App\Domain\Academic\Models;

use App\Domain\Academic\Enums\GradeStage;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $school_id
 * @property int $number
 * @property string $name_km
 * @property string $name_en
 * @property GradeStage $stage
 * @property bool $has_tracks
 * @property-read Collection<int, Classroom> $classrooms
 */
class GradeLevel extends Model
{
    use BelongsToSchool;

    protected $fillable = ['number', 'name_km', 'name_en', 'stage', 'has_tracks'];

    protected function casts(): array
    {
        return ['stage' => GradeStage::class, 'has_tracks' => 'boolean'];
    }

    /** @return HasMany<Classroom, $this> */
    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }
}
