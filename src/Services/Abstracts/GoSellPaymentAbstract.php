<?php

namespace DeltaSpike\GoSell\Services\Abstracts;

use Botble\Payment\Services\Traits\PaymentErrorTrait;
use DeltaSpike\GoSell\Services\TapPayment;
use Botble\Support\Services\ProduceServiceInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

abstract class GoSellPaymentAbstract implements ProduceServiceInterface
{
    use PaymentErrorTrait;

    protected ?string $paymentCurrency = null;

    protected bool $supportRefundOnline;

    protected float $totalAmount;

    public function __construct()
    {
        $this->paymentCurrency = config('plugins.payment.payment.currency');

        $this->totalAmount = 0;

        $this->supportRefundOnline = true;
    }

    public function getSupportRefundOnline(): bool
    {
        return $this->supportRefundOnline;
    }

    public function setCurrency($currency)
    {
        $this->paymentCurrency = $currency;

        return $this;
    }

    public function getCurrency()
    {
        return $this->paymentCurrency;
    }

    public function getPaymentDetails($payment)
    {
        try {
            $params = [
                'from' => $payment->created_at->subDays(1)->toISOString(),
                'to' => $payment->created_at->addDays(1)->toISOString(),
            ];

            $response = (new TapPayment())->getListTransactions($params);
            if ($response['status']) {
                return collect($response['data'])->firstWhere('reference', $payment->charge_id);
            }
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }

        return false;
    }

    public function refundOrder($paymentId, $amount)
    {
        try {
            $response = (new TapPayment())->refundOrder($paymentId, $amount);

            if ($response->status) {


                return [
                    'error' => false,
                    'message' => 'refunded successfully initiated, it might take up to48 hours according to payment gateway.',
                    'data' => $response,
                ];
            }

            return [
                'error' => true,
                'message' => trans('plugins/payment::payment.status_is_not_completed'),
            ];
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return [
                'error' => true,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function getRefundDetails($refundId)
    {
        try {
            $response = (new TapPayment())->getRefundDetails($refundId);
            if ($response['status']) {
                return [
                    'error' => false,
                    'message' => $response['message'],
                    'data' => $response,
                ];
            }

            return [
                'error' => true,
                'message' => trans('plugins/payment::payment.status_is_not_completed'),
            ];
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return [
                'error' => true,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function execute(Request $request)
    {
        try {
            return $this->makePayment($request);
        } catch (Exception $exception) {
            \Log::error($exception);
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }
    }

    abstract public function makePayment(Request $request);

    abstract public function afterMakePayment(Request $request);
}
