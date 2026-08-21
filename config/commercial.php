<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Commercial operation rules
    |--------------------------------------------------------------------------
    |
    | Centralized thresholds used by proactive commercial monitoring.
    | Keep these values configurable so different environments can tune the
    | operation without changing domain code.
    |
    */

    'lead_inactivity_days' => (int) env('LEAD_INACTIVITY_DAYS', 7),
];
