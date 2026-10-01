<?php

return [

    /*
    | Secret used to build keyed hashes of government ID numbers (duplicate detection without
    | storing anything reversible). Generate once and keep it stable:
    |   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
    | Changing it later invalidates existing hashes, so back it up together with APP_KEY.
    */
    'pii_hash_key' => env('HRIMS_PII_HASH_KEY'),
];