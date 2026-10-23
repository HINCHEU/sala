<?php

namespace App\Models;

use App\Domain\People\Models\Teacher;
use App\Domain\School\Enums\MembershipStatus;
use App\Domain\School\Enums\Role;
use App\Domain\School\Models\Membership;
use App\Domain\School\Models\School;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read Membership $pivot set when loaded through School::users()
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
        ];
    }

    /** @return BelongsToMany<School, $this, Membership> */
    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class)->using(Membership::class)->withPivot('status')->withTimestamps();
    }

    /**
     * Schools this user may open: active memberships, or every school for a platform admin.
     *
     * @return Collection<int, School>
     */
    public function accessibleSchools(): Collection
    {
        return $this->is_platform_admin
            ? School::orderBy('name_km')->get()
            : $this->schools()->wherePivot('status', MembershipStatus::Active->value)->orderBy('name_km')->get();
    }

    public function canAccessSchool(School $school): bool
    {
        return $this->is_platform_admin
            || $this->schools()->whereKey($school->id)->wherePivot('status', MembershipStatus::Active->value)->exists();
    }

    /**
     * Role enums held in the current school (spatie team set by ResolveSchool).
     *
     * @return list<Role>
     */
    public function schoolRoles(): array
    {
        return $this->getRoleNames()->map(fn (string $name) => Role::tryFrom($name))->filter()->values()->all();
    }

    public function needsTwoFactor(): bool
    {
        return collect($this->schoolRoles())->contains(fn (Role $role) => $role->requiresTwoFactor());
    }

    public function hasConfirmedTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    public function teacherProfile(): ?Teacher
    {
        return Teacher::where('user_id', $this->id)->first();
    }

    public function isTeacher(): bool
    {
        return collect($this->schoolRoles())->contains(fn (Role $role) => $role->isTeacher());
    }
}
