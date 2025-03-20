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

    public function STKPush(Request $request)
    {
        $amount = $request->input('amount');
        $phoneno = $request->input('phonenumber');
        $account_number = $request->input('account_number');

        // Make sure the callback URL is correct and publicly accessible
        // If testing locally, consider using ngrok to expose your local server
        $callbackUrl = "https://payment.test/api/v1/confirm";

        // Add debug logging
        \Log::info('Initiating STK Push', [
            'phone' => $phoneno,
            'amount' => $amount,
            'account' => $account_number,
            'callback' => $callbackUrl
        ]);

        $response = Mpesa::stkpush($phoneno, $amount, $account_number);

        $result = $response->json();
        \Log::info('STK Push Response', $result);

        if (!is_null($result)) {
            MpesaSTK::create([
                'merchant_request_id' =>  $result['MerchantRequestID'],
                'checkout_request_id' =>  $result['CheckoutRequestID']
            ]);
        }

        return $result;
    }

    public function STKConfirm(Request $request)
    {
        // Log the raw request for debugging
        \Log::info('STK Callback Raw Request', [
            'content' => $request->getContent(),
            'headers' => $request->headers->all()
        ]);

        try {
            // Create an instance of STKPush and call the confirm method
            $stkPush = new STKPush(); // Adjust namespace as needed
            $result = $stkPush->confirm($request);

            // Check if the confirmation failed
            if ($result->failed) {
                \Log::error('STK Push confirmation failed: ' . $result->response);
            } else {
                \Log::info('STK Push confirmation successful');
            }
        } catch (\Exception $e) {
            \Log::error('Exception in STK Callback: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Always respond with success to Safaricom
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Success'
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
