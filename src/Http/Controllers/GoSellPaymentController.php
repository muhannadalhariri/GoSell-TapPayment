<?php

namespace DeltaSpike\GoSell\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Supports\PaymentHelper;
use DeltaSpike\GoSell\Services\Gateways\GoSellPaymentService;
use DeltaSpike\GoSell\Services\TapPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class GoSellPaymentController extends BaseController
{
    public function getPaymentStatus(Request $request, GoSellPaymentService $goSellPaymentService,BaseHttpResponse $response)
    {
        $charge = $goSellPaymentService->getPaymentStatus($request);

        if (!$charge || $charge->status != "CAPTURED") {
            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->setMessage("Payment  reference not found");
        }

        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
            'amount' => $charge->amount / 100,
            'currency' => $charge->currency,
            'charge_id' => $charge->id,
            'payment_channel' => GOSELL_PAYMENT_METHOD_NAME,
            'status' => PaymentStatusEnum::COMPLETED,
            'customer_id' => null ,
            'customer_type' => $charge->metadata->customer_type,
            'payment_type' => 'direct',
            'order_id' => $charge->reference->order,
        ], $request);

        return $response
            ->setNextUrl(PaymentHelper::getRedirectURL())
            ->setMessage(__('Checkout successfully!'));
    }
}
