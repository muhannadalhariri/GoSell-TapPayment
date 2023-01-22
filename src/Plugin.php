<?php

namespace DeltaSpike\GoSell;

use Botble\PluginManagement\Abstracts\PluginOperationAbstract;
use Botble\Setting\Models\Setting;

class Plugin extends PluginOperationAbstract
{
    public static function remove()
    {
        Setting::query()
            ->whereIn('key', [
                'payment_gosell_name',
                'payment_gosell_description',
                'payment_gosell_secret',
                'payment_gosell_merchant_email',
                'payment_gosell_status',
            ])
            ->delete();
    }
}
