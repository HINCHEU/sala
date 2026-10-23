<?php

namespace App\Domain\Academic\Models;

use App\Domain\Academic\Enums\SubjectCategory;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $school_id
 * @property string $code
 * @property string $name_km
 * @property string $name_en
 * @property SubjectCategory $category
 */
class Subject extends Model
{
    use BelongsToSchool;

    protected $fillable = ['code', 'name_km', 'name_en', 'category'];

    protected function casts(): array
    {
        return ['category' => SubjectCategory::class];
    }
}
