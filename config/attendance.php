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
];
