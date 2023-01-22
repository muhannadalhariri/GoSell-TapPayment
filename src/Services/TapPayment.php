<?php

namespace DeltaSpike\GoSell\Services;

use Exception;
use TapPayments\GoSell;
use TapPayments\GoSell\Charges;
use function Sodium\randombytes_random16;

class TapPayment
{
    public function refundOrder($paymentId, $amount)
    {
        \Log::debug("Payment Id ".$paymentId);
        $refund = GoSell\Refunds::create([
            'charge_id'=>$paymentId,
            'amount' => $amount,
            'currency'=>'KWD',
            "reason" => "requested_by_customer",
        ]);
        \Log::debug(json_encode($refund));
        //throw new Exception('Invalid Refund Order GOSELL');
        return $refund;
    }

    protected function getResponse(): array
    {
        return json_decode($this->response->getBody(), true);
    }

    public function isValid(): bool
    {
        return $this->getResponse()['status'];
    }

    public function getPaymentDetails($transactionId)
    {
        $relativeUrl = '/transaction/' . $transactionId;

        $this->response = $this->client->get($this->baseUrl . $relativeUrl);

        if ($this->isValid()) {
            return $this->getResponse();
        }

        throw new Exception('Invalid Get Payment Details GOSELL');
    }

    public function getListTransactions(array $params = [])
    {
        $relativeUrl = '/transaction' . ($params ? ('?' . http_build_query($params)) : '');

        $this->response = $this->client->get($this->baseUrl . $relativeUrl);

        if ($this->isValid()) {
            return $this->getResponse();
        }

        throw new Exception('Invalid Get List Transactions GOSELL');
    }

    public function getRefundDetails($refundId)
    {
        $relativeUrl = '/refund/' . $refundId;

        $this->response = $this->client->get($this->baseUrl . $relativeUrl);

        if ($this->isValid()) {
            return $this->getResponse();
        }

        throw new Exception('Invalid Refund Order GOSELL');
    }

    public static function getAuthorizationResponse(){

    }

    public static function genTranxRef(){
        return rand(1000-100000);
    }
}
