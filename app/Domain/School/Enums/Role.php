<?php

namespace App\Domain\School\Enums;

/**
 * Roles granted per school (spatie teams = school_id). Platform super admin is users.is_platform_admin.
 */
enum Role: string
{
    case Director = 'director';
    case AcademicOffice = 'academic_office';
    case Registrar = 'registrar';
    case Accountant = 'accountant';
    case HomeroomTeacher = 'homeroom_teacher';
    case SubjectTeacher = 'subject_teacher';
    case Guardian = 'guardian';
    case Student = 'student';

    /** @return list<string> */
    public function permissions(): array
    {
        return match ($this) {
            self::Director => [Permission::ManageSettings->value, Permission::ManageMembers->value],
            default => [],
        };
    }

    public function requiresTwoFactor(): bool
    {
        return in_array($this, [self::Director, self::AcademicOffice, self::Accountant], true);
    }

    public function isTeacher(): bool
    {
        return in_array($this, [self::HomeroomTeacher, self::SubjectTeacher], true);
    }

    public function label(): string
    {
        return __('roles.'.$this->value);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
