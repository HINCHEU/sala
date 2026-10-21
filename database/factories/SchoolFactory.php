<?php

namespace Database\Factories;

use App\Domain\School\Enums\SchoolStage;
use App\Domain\School\Enums\SchoolType;
use App\Domain\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('school-????'),
            'name_km' => 'សាលា '.fake()->unique()->numberBetween(1, 99999),
            'name_en' => fake()->company().' School',
            'type' => SchoolType::Public,
            'stage' => SchoolStage::K12,
            'province' => 'ភ្នំពេញ',
        ];
    }
}
