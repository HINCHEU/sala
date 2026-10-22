<?php

namespace App\Domain\People\Models;

use App\Domain\People\Enums\DocumentType;
use App\Domain\People\Enums\Gender;
use App\Domain\People\Enums\StudentStatus;
use App\Support\Files\StoredFile;
use App\Support\PhoneNumber;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A student belongs to the school for life; a yearly Enrollment puts them in a class.
 *
 * @property Gender $gender
 * @property StudentStatus $status
 * @property ?Carbon $dob
 * @property ?Carbon $admitted_at
 */
class Student extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'student_code', 'birth_cert_no', 'last_name_km', 'first_name_km', 'last_name_en', 'first_name_en',
        'gender', 'dob', 'birth_village', 'birth_commune', 'birth_district', 'birth_province', 'address',
        'id_poor_level', 'disability', 'admitted_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'status' => StudentStatus::class,
            'dob' => 'date:Y-m-d',
            'admitted_at' => 'date:Y-m-d',
            // Sensitive: encrypted at rest, shown only on people pages.
            'birth_cert_no' => 'encrypted',
            'id_poor_level' => 'encrypted',
            'disability' => 'encrypted',
        ];
    }

    public function nameKm(): string
    {
        return trim("{$this->last_name_km} {$this->first_name_km}");
    }

    public function nameEn(): ?string
    {
        return trim("{$this->last_name_en} {$this->first_name_en}") ?: null;
    }

    /** @return BelongsToMany<Guardian, $this, GuardianLink> */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class)->using(GuardianLink::class)
            ->withPivot('relationship', 'is_primary_contact', 'can_pickup')
            ->withTimestamps()
            ->orderByPivot('is_primary_contact', 'desc');
    }

    /** @return HasMany<Enrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** @return MorphOne<StoredFile, $this> */
    public function photo(): MorphOne
    {
        return $this->morphOne(StoredFile::class, 'owner')->where('collection', 'photo');
    }

    /** @return MorphMany<StoredFile, $this> */
    public function documents(): MorphMany
    {
        return $this->morphMany(StoredFile::class, 'owner')
            ->whereIn('collection', array_column(DocumentType::cases(), 'value'))->latest('id');
    }

    /**
     * Khmer or Latin name words, student code, or a guardian's phone written any common way.
     *
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);
        if ($term === '') {
            return;
        }

        $phone = PhoneNumber::normalize($term);

        $query->where(function (Builder $query) use ($term, $phone) {
            $query->where(function (Builder $query) use ($term) {
                foreach (preg_split('/\s+/u', $term) ?: [] as $word) {
                    $like = '%'.addcslashes($word, '%_\\').'%';
                    $query->where(fn (Builder $q) => $q->where('last_name_km', 'like', $like)
                        ->orWhere('first_name_km', 'like', $like)
                        ->orWhere('last_name_en', 'like', $like)
                        ->orWhere('first_name_en', 'like', $like)
                        ->orWhere('student_code', 'like', $like));
                }
            });

            if ($phone) {
                $query->orWhereHas('guardians', fn (Builder $g) => $g->where('phone', $phone));
            }
        });
    }

    /**
     * Khmer alphabetical order: how class lists and roll numbers are sorted.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOrderByKhmerName(Builder $query): void
    {
        $query->orderBy('last_name_km')->orderBy('first_name_km')->orderBy('students.id');
    }
}
