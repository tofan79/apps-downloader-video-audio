<?php

return [
    /*
    |--------------------------------------------------------------------------
    | appsDLP Configuration
    |--------------------------------------------------------------------------
    */

    'download_path' => env('DOWNLOAD_PATH', storage_path('downloads')),
    'cookies_path' => env('COOKIES_PATH', storage_path('cookies/cookies.txt')),
    'ytdlp_path' => env('YTDLP_PATH', '/usr/local/bin/yt-dlp'),
    'max_concurrent' => env('MAX_CONCURRENT', 3),
];
