<?php

namespace App\Domain\Academic\Actions;

use App\Domain\Academic\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

class CreateAcademicYear
{
    public function __construct(private SetCurrentYear $setCurrent) {}

    /**
     * Creates the year with Semester 1 and 2 (dates filled in later). A school's first year becomes current.
     *
     * @param  array{name: string, start_date: string, end_date: string}  $data
     */
    public function handle(array $data): AcademicYear
    {
        return DB::transaction(function () use ($data) {
            $isFirst = ! AcademicYear::exists();
            $year = AcademicYear::create($data);

            $year->terms()->createMany([
                ['name_km' => 'ឆមាសទី១', 'name_en' => 'Semester 1', 'sort' => 1],
                ['name_km' => 'ឆមាសទី២', 'name_en' => 'Semester 2', 'sort' => 2],
            ]);

            if ($isFirst) {
                $this->setCurrent->handle($year);
            }

            return $year;
        });
    }
}
