<?php

return [
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),
    'max_image_bytes' => (int) env('AURORA_MAX_IMAGE_BYTES', 10 * 1024 * 1024),
];
