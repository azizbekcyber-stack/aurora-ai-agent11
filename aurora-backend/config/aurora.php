<?php

return [
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),
    'dashboard_token' => env('AURORA_DASHBOARD_TOKEN'),
    'max_image_bytes' => (int) env('AURORA_MAX_IMAGE_BYTES', 10 * 1024 * 1024),
];
