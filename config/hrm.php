<?php

return [

    /*
    |--------------------------------------------------------------------------
    | System / demo employee IDs to exclude from staff pickers
    |--------------------------------------------------------------------------
    */
    'excluded_employee_ids' => array_values(array_filter(array_map(
        'intval',
        explode(',', (string) env('HRM_EXCLUDED_EMPLOYEE_IDS', '14'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Attendance punch geofence
    |--------------------------------------------------------------------------
    | Enforced only when the company record has usable coordinates.
    | Company latitude may be "lat" or "lat,lng". Longitude iframe embeds are
    | parsed for @lat,lng / q=lat,lng when possible. Missing coords = no block.
    */
    'geofence_enabled' => filter_var(env('HRM_GEOFENCE_ENABLED', true), FILTER_VALIDATE_BOOL),
    'geofence_radius_meters' => (int) env('HRM_GEOFENCE_RADIUS_METERS', 500),

    /*
    |--------------------------------------------------------------------------
    | Default password for hired candidates / new employees
    |--------------------------------------------------------------------------
    */
    'default_employee_password' => env('HRM_DEFAULT_EMPLOYEE_PASSWORD', 'Welcome@123'),

];
