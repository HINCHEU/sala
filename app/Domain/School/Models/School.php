<?php

namespace App\Domain\School\Models;

use App\Domain\School\Enums\MembershipStatus;
use App\Domain\School\Enums\SchoolStage;
use App\Domain\School\Enums\SchoolType;
use App\Domain\School\Settings\SchoolSettings;
use App\Models\User;
use App\Support\Files\StoredFile;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property SchoolSettings $settings
 */
#[UseFactory(SchoolFactory::class)]
class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'code', 'name_km', 'name_en', 'type', 'stage', 'province', 'district', 'commune',
        'moeys_code', 'brand_color', 'brand_color_soft', 'report_header', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'type' => SchoolType::class,
            'stage' => SchoolStage::class,
            'settings' => SchoolSettings::class.':default',
        ];
    }

    /** @return BelongsToMany<User, $this, Membership> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->using(Membership::class)->withPivot('status')->withTimestamps();
    }

    /** @return BelongsToMany<User, $this, Membership> */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('status', MembershipStatus::Active->value);
    }

    /** @return MorphOne<StoredFile, $this> */
    public function logo(): MorphOne
    {
        return $this->morphOne(StoredFile::class, 'owner')->where('collection', 'logo');
    }

    public function name(): string
    {
        return app()->getLocale() === 'en' ? $this->name_en : $this->name_km;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
