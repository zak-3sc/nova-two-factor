<?php

return [
    'enabled' => env('NOVA_TWO_FA_ENABLED',true),
    'user_table' => 'users',
    'user_id_column' => 'id',
    'user_model' => \App\Models\User::class,
    '2fa_model' => \Visanduma\NovaTwoFactor\Models\TwoFa::class,
];
