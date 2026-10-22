<?php

namespace Database\Seeders;

use App\Domain\Academic\Actions\ApplyMoeysTemplate;
use App\Domain\Academic\Actions\CreateAcademicYear;
use App\Domain\Academic\Enums\Shift;
use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\GradeLevel;
use App\Domain\Academic\Models\Room;
use App\Domain\Academic\Models\Track;
use App\Domain\School\Actions\AddMember;
use App\Domain\School\Actions\CreateSchool;
use App\Domain\School\BrandColor;
use App\Domain\School\Enums\Role;
use App\Domain\School\Enums\SchoolStage;
use App\Domain\School\Enums\SchoolType;
use App\Domain\School\Models\School;
use App\Models\User;
use App\Support\Files\FileStore;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\ImageManager;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo data. Every account's password is "password" (UserFactory).
     * Platform admin: admin@sala.test. School users: {role}@{code}.sala.test, e.g. director@demo1.sala.test;
     * guardians sign in by phone (012 000 101 for demo1, 012 000 201 for demo2).
     * Each school gets the MoEYS template, year 2026–2027 and classes 7A, 7B, 12A. Students arrive in M3.
     */
    public function run(): void
    {
        $this->demoUser([
            'name' => 'អ្នកគ្រប់គ្រងប្រព័ន្ធ',
            'email' => 'admin@sala.test',
            'is_platform_admin' => true,
        ]);

        $schools = [
            [
                'code' => 'demo1', 'name_km' => 'សាលាសាកល្បង ១', 'name_en' => 'Sala Demo School 1',
                'type' => SchoolType::Public, 'stage' => SchoolStage::K12, 'province' => 'ភ្នំពេញ', 'color' => null,
            ],
            [
                'code' => 'demo2', 'name_km' => 'សាលាសាកល្បង ២', 'name_en' => 'Sala Demo School 2',
                'type' => SchoolType::Private, 'stage' => SchoolStage::Secondary, 'province' => 'សៀមរាប', 'color' => '#0f766e',
            ],
        ];

        foreach ($schools as $n => $data) {
            $color = $data['color'];
            unset($data['color']);

            $school = app(CreateSchool::class)->handle([
                ...$data,
                'brand_color' => $color,
                'brand_color_soft' => $color ? BrandColor::soft($color) : null,
            ]);
            $this->logo($school, $color ?? '#1f4fbf');
            app(CurrentSchool::class)->runAs($school, fn () => $this->academicYear());

            foreach (Role::cases() as $i => $role) {
                $user = $this->demoUser([
                    'name' => $role->label().' '.$school->code,
                    'email' => "{$role->value}@{$school->code}.sala.test",
                    'phone' => $role === Role::Guardian ? '+85512000'.($n + 1).'01' : null,
                ]);
                app(AddMember::class)->handle($school, $user, $role);
            }
        }
    }

    /**
     * Not the factory: Faker is a dev dependency, and production runs this seeder too (make seed).
     *
     * @param  array<string, mixed>  $attributes
     */
    private function demoUser(array $attributes): User
    {
        static $password;

        return User::forceCreate([...$attributes, 'password' => $password ??= Hash::make('password'), 'email_verified_at' => now()]);
    }

    /** MoEYS grades and subjects, the current year and a few classes. Curriculum max scores are left to the school. */
    private function academicYear(): void
    {
        app(ApplyMoeysTemplate::class)->handle();
        $year = app(CreateAcademicYear::class)->handle(['name' => '2026–2027', 'start_date' => '2026-11-01', 'end_date' => '2027-08-31']);
        $room = Room::create(['name' => 'A1', 'building' => 'A', 'capacity' => 45]);

        $grade = fn (int $n) => GradeLevel::where('number', $n)->value('id');
        foreach ([['7A', 7, null, Shift::Morning, $room->id], ['7B', 7, null, Shift::Afternoon, null], ['12A', 12, 'SCI', Shift::Morning, null]] as [$name, $n, $track, $shift, $roomId]) {
            Classroom::create([
                'academic_year_id' => $year->id, 'grade_level_id' => $grade($n), 'name' => $name, 'shift' => $shift,
                'track_id' => $track ? Track::where('code', $track)->value('id') : null,
                'room_id' => $roomId, 'capacity' => 45,
            ]);
        }
    }

    /** A plain coloured disc so each demo school shows its own logo. */
    private function logo(School $school, string $color): void
    {
        $image = ImageManager::gd()->create(256, 256);
        $image->drawCircle(128, 128, function ($circle) use ($color) {
            $circle->radius(120);
            $circle->background($color);
        });
        $image->drawCircle(128, 128, function ($circle) {
            $circle->radius(56);
            $circle->background('#ffffff');
        });

        app(FileStore::class)->putContents((string) $image->toPng(), 'png', 'image/png', $school, 'logo', $school->id, public: true);
    }
}
