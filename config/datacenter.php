<?php

return
    [
        'gcore' => [
            'api_key'      => env('GCORE_API_KEY', 'default'),
            'project_id'   => env('GCORE_PROJECT_ID', '460993'),
            'api_base_v1'  => env('GCORE_API_BASE_V1', 'https://api.gcore.com/cloud/v1'),
            'api_base_v2'  => env('GCORE_API_BASE_V2', 'https://api.gcore.com/cloud/v2'),
        ],
    ];
