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

        // Add debug logging
        \Log::info('Initiating STK Push', [
            'phone' => $phoneno,
            'amount' => $amount,
            'account' => $account_number
        ]);

        $response = Mpesa::stkpush($phoneno, $amount, $account_number);

        $result = $response->json();
        \Log::info('STK Push Response', $result);

        if (
            !is_null($result) &&
            isset($result['MerchantRequestID']) &&
            isset($result['CheckoutRequestID']) &&
            isset($result['ResponseCode']) &&
            $result['ResponseCode'] === '0'
        ) {  // Only proceed if response code is successful

            try {
                // Add more detailed logging
                \Log::info('Creating initial MpesaSTK record', [
                    'merchant_request_id' => $result['MerchantRequestID'],
                    'checkout_request_id' => $result['CheckoutRequestID']
                ]);

                // Create the record with only the initial data
                // The callback will update with transaction details later
                $stkRecord = MpesaSTK::create([
                    'merchant_request_id' => $result['MerchantRequestID'],
                    'checkout_request_id' => $result['CheckoutRequestID'],
                    'amount' => $amount,
                    'phonenumber' => $phoneno,
                    'account_number' => $account_number,
                    'result_code' => $result['ResponseCode'],
                    'result_desc' => 'Awaiting processing'
                ]);

                \Log::info('Initial MpesaSTK record created successfully', ['record_id' => $stkRecord->id]);

                return response()->json([
                    'success' => true,
                    'message' => $result['CustomerMessage'] ?? 'Request accepted for processing',
                    'data' => $result
                ]);

            } catch (\Exception $e) {
                \Log::error('Failed to create MpesaSTK record', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process payment request',
                    'error' => 'Database error'
                ], 500);
            }
        } else {
            // Handle failed response from Mpesa
            $errorMessage = $result['ResponseDescription'] ?? 'Unknown error occurred';
            $errorCode = $result['ResponseCode'] ?? 'unknown';

            \Log::error('Failed STK Push response', [
                'code' => $errorCode,
                'message' => $errorMessage,
                'result' => $result
            ]);

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => 'Payment gateway error'
            ], 400);
        }
    }


    public function STKConfirm(Request $request)
    {
        // Log the raw request for debugging
        \Log::info('STK Callback Received in Controller', [
            'content_length' => strlen($request->getContent()),
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
                \Log::info('STK Push confirmation successful: ' . $result->response);
            }
        } catch (\Exception $e) {
            \Log::error('Exception in STK Callback controller: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
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
