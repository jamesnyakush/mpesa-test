<?php

namespace App\Mpesa;

use App\Models\MpesaSTK;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// This Class is responsible for getting a response from Safaricom and Storing the Transaction Details to the Database
class STKPush
{
    public bool $failed = false;
    public string $response = 'An Unkown Error Occured';

//    public function confirm(Request $request): static
//    {
//        $payload = json_decode($request->getContent());
//
//        Log::info("STKPush Confirmation");
//        if (property_exists($payload, 'Body') && $payload->Body->stkCallback->ResultCode == '0') {
//            $merchant_request_id = $payload->Body->stkCallback->MerchantRequestID;
//            $checkout_request_id = $payload->Body->stkCallback->CheckoutRequestID;
//            $result_desc = $payload->Body->stkCallback->ResultDesc;
//            $result_code = $payload->Body->stkCallback->ResultCode;
//            $amount = $payload->Body->stkCallback->CallbackMetadata->Item[0]->Value;
//            $mpesa_receipt_number = $payload->Body->stkCallback->CallbackMetadata->Item[1]->Value;
//            $transaction_date = $payload->Body->stkCallback->CallbackMetadata->Item[3]->Value;
//            $phonenumber = $payload->Body->stkCallback->CallbackMetadata->Item[4]->Value;
//
//            $stkPush = MpesaSTK::where('merchant_request_id', $merchant_request_id)
//                ->where('checkout_request_id', $checkout_request_id)->first();
//
//            dump($stkPush);
//
//            $data = [
//                'result_desc' => $result_desc,
//                'result_code' => $result_code,
//                'merchant_request_id' => $merchant_request_id,
//                'checkout_request_id' => $checkout_request_id,
//                'amount' => $amount,
//                'mpesa_receipt_number' => $mpesa_receipt_number,
//                'transaction_date' => $transaction_date,
//                'phonenumber' => $phonenumber,
//            ];
//
//            if ($stkPush) {
//                $stkPush->fill($data)->save();
//            } else {
//                MpesaSTK::create($data);
//            }
//        } else {
//            $this->failed = true;
//        }
//        return $this;
//    }

    public function confirm(Request $request): static
    {
        try {
            \Log::info("STKPush Confirmation Started", ['content' => $request->getContent()]);

            $payload = json_decode($request->getContent());

            // Dump the entire payload for debugging
            \Log::debug('STKPush payload', ['payload' => json_encode($payload)]);

            if (property_exists($payload, 'Body') && property_exists($payload->Body, 'stkCallback')) {
                $stkCallback = $payload->Body->stkCallback;
                $merchant_request_id = $stkCallback->MerchantRequestID;
                $checkout_request_id = $stkCallback->CheckoutRequestID;
                $result_desc = $stkCallback->ResultDesc;
                $result_code = $stkCallback->ResultCode;

                // Find the transaction in the database
                $stkPush = MpesaSTK::where('merchant_request_id', $merchant_request_id)
                    ->where('checkout_request_id', $checkout_request_id)->first();

                if ($result_code == '0' && property_exists($stkCallback, 'CallbackMetadata')) {
                    // Extract values from CallbackMetadata
                    $metadataItems = $stkCallback->CallbackMetadata->Item;
                    $amount = null;
                    $mpesa_receipt_number = null;
                    $transaction_date = null;
                    $phonenumber = null;

                    // Loop through each item to extract values
                    foreach ($metadataItems as $item) {
                        if ($item->Name == "Amount") {
                            $amount = $item->Value;
                        } elseif ($item->Name == "MpesaReceiptNumber") {
                            $mpesa_receipt_number = $item->Value;
                            // Log the receipt number specifically
                            \Log::info('Found MpesaReceiptNumber', ['receipt' => $mpesa_receipt_number]);
                        } elseif ($item->Name == "TransactionDate") {
                            $transaction_date = $item->Value;
                        } elseif ($item->Name == "PhoneNumber") {
                            $phonenumber = $item->Value;
                        }
                    }

                    $data = [
                        'result_desc' => $result_desc,
                        'result_code' => $result_code,
                        'amount' => $amount,
                        'mpesa_receipt_number' => $mpesa_receipt_number,
                        'transaction_date' => $transaction_date,
                        'phonenumber' => $phonenumber,
                    ];

                    // Log the data we're about to save
                    \Log::info('Updating STK Push with data', $data);

                    if ($stkPush) {
                        $stkPush->fill($data)->save();
                        \Log::info('Updated existing STK Push record with receipt number');
                    } else {
                        // If record doesn't exist, create it with merchant and checkout request IDs
                        $data['merchant_request_id'] = $merchant_request_id;
                        $data['checkout_request_id'] = $checkout_request_id;
                        MpesaSTK::create($data);
                        \Log::info('Created new STK Push record with receipt number');
                    }
                } else {
                    // Transaction failed or doesn't have callback metadata
                    if ($stkPush) {
                        $stkPush->result_code = $result_code;
                        $stkPush->result_desc = $result_desc;
                        $stkPush->save();
                        \Log::info('Updated STK Push with failed status');
                    }

                    $this->failed = true;
                    $this->response = "Transaction failed or missing callback metadata. Code: $result_code";
                    \Log::error('STKPush Confirmation: ' . $this->response);
                }
            } else {
                $this->failed = true;
                $this->response = 'Invalid payload structure';
                \Log::error('STKPush Confirmation: Invalid payload structure', ['payload' => json_encode($payload)]);
            }
        } catch (\Exception $e) {
            $this->failed = true;
            $this->response = 'Exception: ' . $e->getMessage();
            \Log::error('Exception in STKPush confirmation: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $this;
    }
}
