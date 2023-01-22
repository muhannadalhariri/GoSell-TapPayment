<?php

namespace DeltaSpike\GoSell\Services\Gateways;

use DeltaSpike\GoSell\Services\Abstracts\GoSellPaymentAbstract;
use Illuminate\Http\Request;
use TapPayments\GoSell;
use TapPayments\GoSell\Charges;

class GoSellPaymentService extends GoSellPaymentAbstract
{
    public function makePayment(Request $request)
    {
        $data = [
            "amount"=> $request->input("amount"),
            "currency"=> $request->input("currency"),
            "threeDSecure"=> true,
            "save_card"=> false,
            "description"=> $request->input("description"),
            "statement_descriptor"=> "Payment for Order ".$request->input("order_id"),
            "metadata"=> [
                "customer_id"=> $request->input("customer_id") ,
                "customer_type" => $request->input("customer_type")
            ],
            "reference"=> [
                "transaction"=> "txn_".rand(100000,1000000)."_".time(),
                "order"=> $request->input("order_id")
            ],
            "receipt"=> [
                "email"=> true,
                "sms"=> true
            ],
            "customer"=> [
                "first_name"=> $request->toArray()['address']['name'],
                "middle_name"=> "",
                "last_name"=> "",
                "email"=> $request->toArray()['address']['email'],
                "phone"=> [
                    "country_code"=> "965",
                    "number"=> $request->toArray()['address']['phone']
                ]
            ],
            "source"=> [
                "id"=> "src_all"
            ],
            "post"=> [
                "url"=> route("gosell.payment.callback")
            ],
            "redirect"=> [
                "url"=> route("gosell.payment.callback")
            ]
        ];
        $charge = Charges::create($data);
        return $charge;
    }

    public function afterMakePayment(Request $request)
    {
    }


    /**
     * List currencies supported https://support.paystack.com/hc/en-us/articles/360009973779
     */
    public function supportedCurrencyCodes(): array
    {
        return [
            'KWD',
            'SAR',
            'QAR',
            'AED',
            'OMR',
            'BAD',
        ];
    }

    public function getPaymentStatus(Request $request){
        $chargeId = $request->input("tap_id");
        $charge = Charges::retrieve($chargeId);
        \Log::debug(json_encode($charge));
        return $charge;
    }
}
