<?php

namespace Visanduma\NovaTwoFactor;

trait ProtectWith2FA
{
    public function twoFa()
    {
        return $this->hasOne(config('nova-two-factor.2fa_model'), 'user_id', config('nova-two-factor.user_id_column'));
    }
}
