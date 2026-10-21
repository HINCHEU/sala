<?php

// shortcut: only the rules used so far; missing keys fall back to lang/en. Add rules as forms need them.
return [
    'required' => 'សូមបំពេញ :attribute។',
    'email' => ':attribute ត្រូវតែជាអ៊ីមែលត្រឹមត្រូវ។',
    'string' => ':attribute ត្រូវតែជាអក្សរ។',
    'max' => [
        'string' => ':attribute មិនអាចលើស :max តួអក្សរ។',
    ],

    'attributes' => [
        'email' => 'អ៊ីមែល',
        'password' => 'ពាក្យសម្ងាត់',
    ],
];
