<?php

namespace DeltaSpike\GoSell\Providers;

use Botble\Ecommerce\Repositories\Interfaces\OrderAddressInterface;
use Botble\Payment\Enums\PaymentMethodEnum;
use DeltaSpike\GoSell\Services\Gateways\GoSellPaymentService;
use Html;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Throwable;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, [$this, 'registerGoSellMethod'], 16, 2);
        $this->app->booted(function () {
            add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, [$this, 'checkoutWithGoSell'], 16, 2);
        });

        add_filter(PAYMENT_METHODS_SETTINGS_PAGE, [$this, 'addPaymentSettings'], 97, 1);

        add_filter(BASE_FILTER_ENUM_ARRAY, function ($values, $class) {
            if ($class == PaymentMethodEnum::class) {
                $values['GOSELL'] = GOSELL_PAYMENT_METHOD_NAME;
            }

            return $values;
        }, 21, 2);

        add_filter(BASE_FILTER_ENUM_LABEL, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == GOSELL_PAYMENT_METHOD_NAME) {
                $value = 'GoSell';
            }

            return $value;
        }, 21, 2);

        add_filter(BASE_FILTER_ENUM_HTML, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == GOSELL_PAYMENT_METHOD_NAME) {
                $value = Html::tag(
                    'span',
                    PaymentMethodEnum::getLabel($value),
                    ['class' => 'label-success status-label']
                )
                    ->toHtml();
            }

            return $value;
        }, 21, 2);

        add_filter(PAYMENT_FILTER_GET_SERVICE_CLASS, function ($data, $value) {
            if ($value == GOSELL_PAYMENT_METHOD_NAME) {
                $data = GoSellPaymentService::class;
            }

            return $data;
        }, 20, 2);

        add_filter(PAYMENT_FILTER_PAYMENT_INFO_DETAIL, function ($data, $payment) {
            if ($payment->payment_channel == GOSELL_PAYMENT_METHOD_NAME) {
                $paymentService = (new GoSellPaymentService());
                $paymentDetail = $paymentService->getPaymentDetails($payment);
                if ($paymentDetail) {
                    $data = view('plugins/gosell::detail', ['payment' => $paymentDetail, 'paymentModel' => $payment])->render();
                }
            }

            return $data;
        }, 20, 2);

        add_filter(PAYMENT_FILTER_GET_REFUND_DETAIL, function ($data, $payment, $refundId) {
            if ($payment->payment_channel == GOSELL_PAYMENT_METHOD_NAME) {
                $refundDetail = (new GoSellPaymentService())->getRefundDetails($refundId);
                if (! Arr::get($refundDetail, 'error')) {
                    $refunds = Arr::get($payment->metadata, 'refunds');
                    $refund = collect($refunds)->firstWhere('data.id', $refundId);
                    $refund = array_merge($refund, Arr::get($refundDetail, 'data', []));

                    return array_merge($refundDetail, [
                        'view' => view('plugins/gosell::refund-detail', ['refund' => $refund, 'paymentModel' => $payment])->render(),
                    ]);
                }

                return $refundDetail;
            }

            return $data;
        }, 20, 3);
    }

    public function addPaymentSettings(?string $settings): string
    {
        return $settings . view('plugins/gosell::settings')->render();
    }

    public function registerGoSellMethod(?string $html, array $data): string
    {
        return $html . view('plugins/gosell::methods', $data)->render();
    }

    public function checkoutWithGoSell(array $data, Request $request)
    {
        if ($request->input('payment_method') == GOSELL_PAYMENT_METHOD_NAME) {
            $supportedCurrencies = (new GoSellPaymentService())->supportedCurrencyCodes();

            if (! in_array($data['currency'], $supportedCurrencies)) {
                $data['error'] = true;
                $data['message'] = __(":name doesn't support :currency. List of currencies supported by :name: :currencies.", [
                    'name' => 'GoSell',
                    'currency' => $data['currency'],
                    'currencies' => implode(', ', $supportedCurrencies),
                ]);

                return $data;
            }

            $orderIds = (array) $request->input('order_id', []);
            $orderId = Arr::first($orderIds);
            $orderAddress = $this->app->make(OrderAddressInterface::class)->getFirstBy(['order_id' => $orderId]);
            $paymentData = apply_filters(PAYMENT_FILTER_PAYMENT_DATA, [], $request);
            \Log::debug(json_encode($paymentData));

            \Log::debug("Customer : ".Arr::get($paymentData, 'customer_id'));
            try {
                $goSellService = $this->app->make(GoSellPaymentService::class);
                $response = $goSellService->execute($request);
                if ($response->status == "INITIATED") {
                    header('Location: ' . $response->transaction->url);
                    exit;
                }

                $data['error'] = true;
                $data['message'] = __('Payment failed!');
            } catch (Throwable $exception) {
                $data['error'] = true;
                $data['message'] = json_encode($exception->getMessage());
            }
        }

        return $data;
    }
}
