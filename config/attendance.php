<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Attendance timezone
    |--------------------------------------------------------------------------
    |
    | All attendance dates and time-in values are derived from server time in
    | this timezone. Do not trust browser-supplied dates or times.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'Asia/Manila'),

    'status_present' => 'Present',

    'photo_max_kilobytes' => 2048,

    'photo_mimes' => ['jpeg', 'jpg', 'png', 'webp'],

    'scan_rate_limit_per_minute' => 30,

    'login_rate_limit_per_minute' => 5,

    'face_descriptor_length' => 128,

    'face_match_threshold' => 0.42,

    'face_ambiguity_margin' => 0.08,

    'weather' => [
        'latitude' => 14.5995,
        'longitude' => 120.9842,
        'cache_seconds' => 1800,
    ],
];
