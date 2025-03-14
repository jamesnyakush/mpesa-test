<?php

namespace App\Http\Controllers;

use App\Models\MpesaSTK;
use App\Mpesa\STKPush;
use Iankumu\Mpesa\Facades\Mpesa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaSTKPUSHController extends Controller
{
    public int $result_code = 1;
    public string $result_desc = 'An error occured';

    // Initiate  Stk Push Request
    public function STKPush(Request $request)
    {
        $amount = $request->input('amount');
        $phoneno = $request->input('phonenumber');
        $account_number = $request->input('account_number');

        $response = Mpesa::stkpush($phoneno, $amount, $account_number,"https://payment.test/api/v1/confirm");

        $result = $response->json();

        if (!is_null($result)) {
            MpesaSTK::create([
                'merchant_request_id' =>  $result['MerchantRequestID'],
                'checkout_request_id' =>  $result['CheckoutRequestID']
            ]);
        }

        return $result;
    }

    // This function is used to review the response from Safaricom once a transaction is complete
    public function STKConfirm(Request $request)
    {
        $stk_push_confirm = (new STKPush())->confirm($request);

        $this->result_code = 0;
        $this->result_desc = 'Success';

        return response()->json([
            'ResultCode' => $this->result_code,
            'ResultDesc' => $this->result_desc
        ]);
    }

    // Used to query the status of an STK Push Transaction
    public function query(Request $request)
    {
        $checkoutRequestId = $request->input('CheckoutRequestID');

        $response = Mpesa::stkquery($checkoutRequestId);

        return json_decode((string)$response);

    }
}
