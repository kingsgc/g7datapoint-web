<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Initial Admin Credentials
    |--------------------------------------------------------------------------
    |
    | Consumed by database/migrations/2026_10_03_1655_update_admin_user.php,
    | which seeds the admin account on a fresh install. Both are optional: when
    | a variable is unset the migration leaves that field alone rather than
    | overwriting a working credential with a blank value.
    |
    |   ADMIN_INITIAL_PASSWORD   stored hashed (bcrypt)
    |   ADMIN_INITIAL_PIN        stored in plain text, because the application
    |                            compares the pin that way everywhere
    |
    | Leave both unset in any environment that already has an admin user.
    |
    */

    'initial_password' => env('ADMIN_INITIAL_PASSWORD'),

    'initial_pin' => env('ADMIN_INITIAL_PIN'),

];