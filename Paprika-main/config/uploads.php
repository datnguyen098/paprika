<?php

return [
    'disk' => env('UPLOAD_DISK', 'public'),
    'max_image_kb' => (int) env('UPLOAD_MAX_IMAGE_KB', 10240),
    'resize_width' => (int) env('UPLOAD_RESIZE_WIDTH', 1600),
    'resize_height' => (int) env('UPLOAD_RESIZE_HEIGHT', 1600),
    'webp_quality' => (int) env('UPLOAD_WEBP_QUALITY', 82),
    'profiles' => [
        'default' => [
            'width' => (int) env('UPLOAD_RESIZE_WIDTH', 1600),
            'height' => (int) env('UPLOAD_RESIZE_HEIGHT', 1600),
            'quality' => (int) env('UPLOAD_WEBP_QUALITY', 86),
        ],
        'hero' => [
            'width' => (int) env('UPLOAD_HERO_WIDTH', 1920),
            'height' => (int) env('UPLOAD_HERO_HEIGHT', 1280),
            'quality' => (int) env('UPLOAD_HERO_QUALITY', 90),
        ],
        'content' => [
            'width' => (int) env('UPLOAD_CONTENT_WIDTH', 1800),
            'height' => (int) env('UPLOAD_CONTENT_HEIGHT', 1800),
            'quality' => (int) env('UPLOAD_CONTENT_QUALITY', 90),
        ],
        'avatar' => [
            'width' => (int) env('UPLOAD_AVATAR_WIDTH', 768),
            'height' => (int) env('UPLOAD_AVATAR_HEIGHT', 768),
            'quality' => (int) env('UPLOAD_AVATAR_QUALITY', 90),
        ],
        'brand' => [
            'width' => (int) env('UPLOAD_BRAND_WIDTH', 640),
            'height' => (int) env('UPLOAD_BRAND_HEIGHT', 640),
            'quality' => (int) env('UPLOAD_BRAND_QUALITY', 95),
        ],
    ],
    'variants' => [
        'thumb' => [
            'width' => (int) env('UPLOAD_THUMB_WIDTH', 360),
            'quality' => (int) env('UPLOAD_THUMB_QUALITY', 82),
        ],
        'card' => [
            'width' => (int) env('UPLOAD_CARD_WIDTH', 720),
            'quality' => (int) env('UPLOAD_CARD_QUALITY', 84),
        ],
        'large' => [
            'width' => (int) env('UPLOAD_LARGE_WIDTH', 1200),
            'quality' => (int) env('UPLOAD_LARGE_QUALITY', 88),
        ],
        'hero' => [
            'width' => (int) env('UPLOAD_HERO_VARIANT_WIDTH', 1920),
            'quality' => (int) env('UPLOAD_HERO_VARIANT_QUALITY', 90),
        ],
    ],
    'variant_profiles' => [
        'thumb' => ['thumb'],
        'card' => ['thumb', 'card'],
        'content' => ['thumb', 'card', 'large'],
        'hero' => ['thumb', 'card', 'large', 'hero'],
        'avatar' => ['thumb', 'card'],
        'brand' => ['thumb', 'card'],
    ],
    'folder_profiles' => [
        'banners' => 'hero',
        'gallery' => 'hero',
        'promotions' => 'hero',
        'dishes' => 'content',
        'posts' => 'content',
        'pages' => 'content',
        'categories' => 'content',
        'users' => 'avatar',
        'testimonials' => 'avatar',
        'settings' => 'content',
    ],
];
