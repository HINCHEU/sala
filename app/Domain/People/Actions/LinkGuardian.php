<?php

namespace App\Domain\People\Actions;

use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\Student;
use Illuminate\Support\Facades\DB;

class LinkGuardian
{
    /**
     * Links a guardian to a student. A guardian with the same phone is reused (siblings share parents);
     * otherwise one is created. A student's first guardian becomes the primary contact.
     *
     * @param  array<string, mixed>  $guardian  name_km, name_en, gender, occupation, phone (+855 form), address
     * @param  array{relationship: string, is_primary_contact?: bool, can_pickup?: bool}  $link
     */
    public function handle(Student $student, array $guardian, array $link): Guardian
    {
        return DB::transaction(function () use ($student, $guardian, $link) {
            $model = $this->findByPhone($guardian['phone'] ?? null) ?? Guardian::create($guardian);

            $primary = ($link['is_primary_contact'] ?? false) || ! $student->guardians()->exists();
            $student->guardians()->syncWithoutDetaching([$model->id => [
                'school_id' => $student->school_id,
                'relationship' => $link['relationship'],
                'is_primary_contact' => $primary,
                'can_pickup' => $link['can_pickup'] ?? true,
            ]]);

            if ($primary) {
                self::makePrimary($student, $model);
            }

            activity()->performedOn($student)->causedBy(auth()->user())
                ->withProperties(['guardian_id' => $model->id, 'relationship' => $link['relationship']])
                ->log('guardian.linked');

            return $model;
        });
    }

    /** The guardian with this phone, restored if it had been removed. The unique key covers removed rows too. */
    public function findByPhone(?string $phone): ?Guardian
    {
        if (! $phone) {
            return null;
        }

        $guardian = Guardian::withTrashed()->where('phone', $phone)->first();
        if ($guardian?->trashed()) {
            $guardian->restore();
        }

        return $guardian;
    }

    public static function makePrimary(Student $student, Guardian $guardian): void
    {
        DB::table('guardian_student')
            ->where('student_id', $student->id)
            ->where('guardian_id', '!=', $guardian->id)
            ->update(['is_primary_contact' => false]);
    }
}
