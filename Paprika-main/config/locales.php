<?php

return [
    'source_locale' => 'vi',
    'default' => 'vi',
    'route_default' => 'vi',
    'supported' => [
        'vi' => [
            'name' => 'Tiếng Việt',
            'native' => 'VI',
            'prefix' => 'vi',
            'og_locale' => 'vi_VN',
            'is_active' => true,
        ],
        'en' => [
            'name' => 'English',
            'native' => 'EN',
            'prefix' => 'en',
            'og_locale' => 'en_US',
            'is_active' => true,
        ],
        'el' => [
            'name' => 'Ελληνικά',
            'native' => 'EL',
            'prefix' => 'el',
            'og_locale' => 'el_GR',
            'is_active' => true,
        ],
    ],
    'translation_targets' => [
        'deepl' => [
            'en' => 'EN-US',
            'el' => 'EL',
        ],
        'microsoft' => [
            'en' => 'en',
            'el' => 'el',
        ],
    ],
];
