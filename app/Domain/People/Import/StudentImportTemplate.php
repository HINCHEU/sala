<?php

namespace App\Domain\People\Import;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/** The empty sheet schools download and fill in: headings only, in StudentSheet's column order. */
class StudentImportTemplate implements FromArray, ShouldAutoSize
{
    /** @return list<list<string>> */
    public function array(): array
    {
        return [StudentSheet::headings()];
    }
}
