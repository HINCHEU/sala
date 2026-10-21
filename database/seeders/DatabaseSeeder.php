<?php

namespace Database\Seeders;

use App\Domain\School\Actions\AddMember;
use App\Domain\School\Actions\CreateSchool;
use App\Domain\School\BrandColor;
use App\Domain\School\Enums\Role;
use App\Domain\School\Enums\SchoolStage;
use App\Domain\School\Enums\SchoolType;
use App\Domain\School\Models\School;
use App\Models\User;
use App\Support\Files\FileStore;
use Illuminate\Database\Seeder;
use Intervention\Image\ImageManager;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo data. Every account's password is "password" (UserFactory).
     * Platform admin: admin@sala.test. School users: {role}@{code}.sala.test, e.g. director@demo1.sala.test;
     * guardians sign in by phone (012 000 101 for demo1, 012 000 201 for demo2).
     * MoEYS subjects and the 200 Khmer-named students arrive with their tables in M2–M3.
     */
    public function run(): void
    {
        User::factory()->create([
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

            foreach (Role::cases() as $i => $role) {
                $user = User::factory()->create([
                    'name' => $role->label().' '.$school->code,
                    'email' => "{$role->value}@{$school->code}.sala.test",
                    'phone' => $role === Role::Guardian ? '+85512000'.($n + 1).'01' : null,
                ]);
                app(AddMember::class)->handle($school, $user, $role);
            }
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
