<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Recursos que podem variar conforme o ambiente da aplicação.
    | Em produção, recursos de teste devem permanecer desabilitados.
    |
    */

    'public_registration' => env('PUBLIC_REGISTRATION_ENABLED', false),

    'tester_access' => env('TESTER_ACCESS_ENABLED', false),
];
