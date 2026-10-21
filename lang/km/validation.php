<?php

// shortcut: only the rules the forms use so far; missing keys fall back to lang/en. Add rules as forms need them.
return [
    'required' => 'សូមបំពេញ :attribute។',
    'required_without' => 'សូមបំពេញ :attribute ឬ :values។',
    'email' => ':attribute ត្រូវតែជាអ៊ីមែលត្រឹមត្រូវ។',
    'string' => ':attribute ត្រូវតែជាអក្សរ។',
    'numeric' => ':attribute ត្រូវតែជាលេខ។',
    'boolean' => ':attribute ត្រូវតែជា បាទ/ចាស ឬ ទេ។',
    'in' => ':attribute ដែលបានជ្រើសមិនត្រឹមត្រូវ។',
    'enum' => ':attribute ដែលបានជ្រើសមិនត្រឹមត្រូវ។',
    'regex' => 'ទម្រង់ :attribute មិនត្រឹមត្រូវ។',
    'unique' => ':attribute នេះមានគេប្រើរួចហើយ។',
    'confirmed' => 'ការបញ្ជាក់ :attribute មិនដូចគ្នា។',
    'current_password' => 'ពាក្យសម្ងាត់មិនត្រឹមត្រូវ។',
    'image' => ':attribute ត្រូវតែជារូបភាព។',
    'mimes' => ':attribute ត្រូវតែជាឯកសារប្រភេទ :values។',
    'phone' => ':attribute ត្រូវតែជាលេខទូរស័ព្ទកម្ពុជា ឧ. 012 345 678។',
    'between' => [
        'numeric' => ':attribute ត្រូវនៅចន្លោះ :min និង :max។',
    ],
    'max' => [
        'string' => ':attribute មិនអាចលើស :max តួអក្សរ។',
        'file' => ':attribute មិនអាចធំជាង :max KB។',
    ],
    'min' => [
        'string' => ':attribute ត្រូវមានយ៉ាងហោចណាស់ :min តួអក្សរ។',
    ],
    'password' => [
        'letters' => ':attribute ត្រូវមានអក្សរយ៉ាងហោចណាស់មួយ។',
        'mixed' => ':attribute ត្រូវមានអក្សរធំ និងអក្សរតូច។',
        'numbers' => ':attribute ត្រូវមានលេខយ៉ាងហោចណាស់មួយ។',
        'symbols' => ':attribute ត្រូវមាននិមិត្តសញ្ញាយ៉ាងហោចណាស់មួយ។',
        'uncompromised' => ':attribute នេះបានលេចធ្លាយលើអ៊ីនធឺណិត។ សូមជ្រើសពាក្យសម្ងាត់ផ្សេង។',
    ],

    'attributes' => [
        'login' => 'អ៊ីមែល ឬលេខទូរស័ព្ទ',
        'email' => 'អ៊ីមែល',
        'phone' => 'លេខទូរស័ព្ទ',
        'name' => 'ឈ្មោះ',
        'password' => 'ពាក្យសម្ងាត់',
        'current_password' => 'ពាក្យសម្ងាត់បច្ចុប្បន្ន',
        'code' => 'កូដ',
        'name_km' => 'ឈ្មោះជាភាសាខ្មែរ',
        'name_en' => 'ឈ្មោះជាភាសាអង់គ្លេស',
        'type' => 'ប្រភេទ',
        'stage' => 'កម្រិតសិក្សា',
        'province' => 'ខេត្ត/រាជធានី',
        'role' => 'តួនាទី',
        'logo' => 'និមិត្តសញ្ញា',
        'brand_color' => 'ពណ៌សាលា',
        'report_header' => 'អត្ថបទក្បាល',
    ],
];
