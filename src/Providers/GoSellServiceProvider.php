<?php

namespace DeltaSpike\GoSell\Providers;

use Botble\Base\Traits\LoadAndPublishDataTrait;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use TapPayments\GoSell;

class GoSellServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot(): void
    {
        if (is_plugin_active('payment')) {
            $this->setNamespace('plugins/gosell')
                ->loadHelpers()
                ->loadRoutes()
                ->loadAndPublishViews()
                ->publishAssets();

            File::requireOnce(__DIR__ . '/../../vendor/autoload.php');

            $this->app->register(HookServiceProvider::class);

            $config = $this->app['config'];

            $config->set([
                'gosell.private' => get_payment_setting('private', GOSELL_PAYMENT_METHOD_NAME),
            ]);
            GoSell::setPrivateKey($config->get('gosell.private'));
        }
    }
}
