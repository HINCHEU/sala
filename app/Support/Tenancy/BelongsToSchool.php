<?php

namespace App\Support\Tenancy;

use App\Domain\School\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scopes every query to the current school and fills school_id on create.
 * With no current school (console, platform pages) the scope is off, so platform code must filter itself.
 */
trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school', function (Builder $query) {
            if ($id = app(CurrentSchool::class)->id()) {
                $query->where($query->qualifyColumn('school_id'), $id);
            }
        });

        static::creating(function ($model) {
            $model->school_id ??= app(CurrentSchool::class)->idOrFail();
        });
    }

    /** @return BelongsTo<School, $this> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
