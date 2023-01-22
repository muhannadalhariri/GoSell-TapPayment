<?php

Route::group(['namespace' => 'DeltaSpike\GoSell\Http\Controllers', 'middleware' => ['web', 'core']], function () {
    Route::get('gosell/payment/callback', [
        'as' => 'gosell.payment.callback',
        'uses' => 'GoSellPaymentController@getPaymentStatus',
    ]);
});
