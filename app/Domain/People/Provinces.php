<?php

namespace App\Domain\People;

/**
 * Cambodia's 25 provinces, stored by Khmer name (as schools.province is).
 * shortcut: district, commune and village are free text, not cascading selects; that needs the official
 * NCDD gazetteer (about 14,000 villages) imported as a table. Upgrade when a school asks for clean village data.
 */
final class Provinces
{
    public const ALL = [
        'បន្ទាយមានជ័យ' => 'Banteay Meanchey',
        'បាត់ដំបង' => 'Battambang',
        'កំពង់ចាម' => 'Kampong Cham',
        'កំពង់ឆ្នាំង' => 'Kampong Chhnang',
        'កំពង់ស្ពឺ' => 'Kampong Speu',
        'កំពង់ធំ' => 'Kampong Thom',
        'កំពត' => 'Kampot',
        'កណ្ដាល' => 'Kandal',
        'កែប' => 'Kep',
        'កោះកុង' => 'Koh Kong',
        'ក្រចេះ' => 'Kratie',
        'មណ្ឌលគិរី' => 'Mondulkiri',
        'ឧត្ដរមានជ័យ' => 'Oddar Meanchey',
        'ប៉ៃលិន' => 'Pailin',
        'ភ្នំពេញ' => 'Phnom Penh',
        'ព្រះសីហនុ' => 'Preah Sihanouk',
        'ព្រះវិហារ' => 'Preah Vihear',
        'ព្រៃវែង' => 'Prey Veng',
        'ពោធិ៍សាត់' => 'Pursat',
        'រតនគិរី' => 'Ratanakiri',
        'សៀមរាប' => 'Siem Reap',
        'ស្ទឹងត្រែង' => 'Stung Treng',
        'ស្វាយរៀង' => 'Svay Rieng',
        'តាកែវ' => 'Takeo',
        'ត្បូងឃ្មុំ' => 'Tboung Khmum',
    ];

    /** The Khmer name for a province typed in Khmer or English (any case), or null if it is not one. */
    public static function find(?string $name): ?string
    {
        $name = trim((string) $name);
        if (isset(self::ALL[$name])) {
            return $name;
        }

        $key = array_search(mb_strtolower($name), array_map('mb_strtolower', self::ALL), true);

        return $key === false ? null : $key;
    }
}
