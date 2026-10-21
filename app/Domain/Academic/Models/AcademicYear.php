<?php

namespace App\Domain\Academic\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use BelongsToSchool;

    protected $fillable = ['name', 'start_date', 'end_date', 'is_current'];

    protected function casts(): array
    {
        return ['start_date' => 'date:Y-m-d', 'end_date' => 'date:Y-m-d', 'is_current' => 'boolean'];
    }

    /** @return HasMany<Term, $this> */
    public function terms(): HasMany
    {
        return $this->hasMany(Term::class)->orderBy('sort');
    }

    public static function current(): ?self
    {
        return static::where('is_current', true)->first();
    }
}
