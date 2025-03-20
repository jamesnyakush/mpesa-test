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

    public function confirm(Request $request): static
    {
        try {
            // Log the raw request for debugging
            \Log::info("STKPush Confirmation Raw Request", ['content' => $request->getContent()]);

            $payload = json_decode($request->getContent());

            // Check if JSON is valid
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->failed = true;
                $this->response = 'Invalid JSON payload: ' . json_last_error_msg();
                \Log::error('STKPush Confirmation: ' . $this->response, ['raw_content' => $request->getContent()]);
                return $this;
            }

            // Log the decoded payload
            \Log::info('STKPush Decoded Payload', ['payload' => json_encode($payload)]);

            // Check if payload has the expected structure
            if (!property_exists($payload, 'Body') || !property_exists($payload->Body, 'stkCallback')) {
                $this->failed = true;
                $this->response = 'Missing Body or stkCallback in payload';
                \Log::error('STKPush Confirmation: ' . $this->response);
                return $this;
            }

            $stkCallback = $payload->Body->stkCallback;

            // Extract the necessary data
            $merchant_request_id = $stkCallback->MerchantRequestID;
            $checkout_request_id = $stkCallback->CheckoutRequestID;
            $result_desc = $stkCallback->ResultDesc;
            $result_code = $stkCallback->ResultCode;

            // Find the transaction in the database
            $stkPush = MpesaSTK::where('merchant_request_id', $merchant_request_id)
                ->where('checkout_request_id', $checkout_request_id)->first();

            \Log::info('Found STK Push record', ['found' => (bool)$stkPush, 'id' => $stkPush ? $stkPush->id : null]);

            if ($result_code == '0' && property_exists($stkCallback, 'CallbackMetadata')) {
                // Transaction was successful, extract the metadata
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
                    } elseif ($item->Name == "TransactionDate") {
                        $transaction_date = $item->Value;
                    } elseif ($item->Name == "PhoneNumber") {
                        $phonenumber = $item->Value;
                    }
                }

                // Log the extracted data
                \Log::info('Extracted callback data', [
                    'amount' => $amount,
                    'receipt' => $mpesa_receipt_number,
                    'date' => $transaction_date,
                    'phone' => $phonenumber
                ]);

                if ($stkPush) {
                    // Update the existing record
                    $stkPush->result_desc = $result_desc;
                    $stkPush->result_code = $result_code;
                    $stkPush->amount = $amount;
                    $stkPush->mpesa_receipt_number = $mpesa_receipt_number;
                    $stkPush->transaction_date = $transaction_date;
                    $stkPush->phonenumber = $phonenumber;

                    $saved = $stkPush->save();
                    \Log::info('Updated existing STK Push record', ['success' => $saved]);
                } else {
                    // Create a new record if not found
                    $data = [
                        'merchant_request_id' => $merchant_request_id,
                        'checkout_request_id' => $checkout_request_id,
                        'result_desc' => $result_desc,
                        'result_code' => $result_code,
                        'amount' => $amount,
                        'mpesa_receipt_number' => $mpesa_receipt_number,
                        'transaction_date' => $transaction_date,
                        'phonenumber' => $phonenumber,
                    ];

                    $newRecord = MpesaSTK::create($data);
                    \Log::info('Created new STK Push record', ['id' => $newRecord->id]);
                }
            } else {
                // Transaction failed or doesn't have callback metadata
                if ($stkPush) {
                    $stkPush->result_code = $result_code;
                    $stkPush->result_desc = $result_desc;
                    $saved = $stkPush->save();
                    \Log::info('Updated STK Push with failed status', ['success' => $saved]);
                }

                $this->failed = ($result_code != '0');
                $this->response = "Transaction status: $result_desc (Code: $result_code)";
                \Log::info('STKPush Confirmation: ' . $this->response);
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
