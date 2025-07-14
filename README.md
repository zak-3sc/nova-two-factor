# nova-two-factor

Forked from https://github.com/Visanduma/nova-two-factor

It's almost identical to v1 of said repository (which is Nova 3 compatible), with a fix for local QR code generation and a few visual tweaks.

1. Pubish config & migration

`php artisan vendor:publish --provider="Visanduma\NovaTwoFactor\ToolServiceProvider"`

```
return [
    'enabled' => env('NOVA_TWO_FA_ENABLE',true),
    'user_table' => 'users',
    'user_id_column' => 'id',
    'user_model' => \App\Models\User::class,
    '2fa_model' => \Visanduma\NovaTwoFactor\Models\TwoFa::class,
];
```

2. Use ProtectWith2FA trait in User model

`use ProtectWith2FA`

3. Add TwoFa middleware to Nova config file

`\Visanduma\NovaTwoFactor\Http\Middleware\TwoFa::class`

4. Register NovaTwoFactor tool in Nova Service Provider

`new \Visanduma\NovaTwoFactor\NovaTwoFactor()`

5. Run `php artisan migrate`
6. You are done!
