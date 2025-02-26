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
        $payload = json_decode($request->getContent());

        Log::info("STKPush Confirmation");
        if (property_exists($payload, 'Body') && $payload->Body->stkCallback->ResultCode == '0') {
            $merchant_request_id = $payload->Body->stkCallback->MerchantRequestID;
            $checkout_request_id = $payload->Body->stkCallback->CheckoutRequestID;
            $result_desc = $payload->Body->stkCallback->ResultDesc;
            $result_code = $payload->Body->stkCallback->ResultCode;
            $amount = $payload->Body->stkCallback->CallbackMetadata->Item[0]->Value;
            $mpesa_receipt_number = $payload->Body->stkCallback->CallbackMetadata->Item[1]->Value;
            $transaction_date = $payload->Body->stkCallback->CallbackMetadata->Item[3]->Value;
            $phonenumber = $payload->Body->stkCallback->CallbackMetadata->Item[4]->Value;

            $stkPush = MpesaSTK::where('merchant_request_id', $merchant_request_id)
                ->where('checkout_request_id', $checkout_request_id)->first();

            dump($stkPush);

            $data = [
                'result_desc' => $result_desc,
                'result_code' => $result_code,
                'merchant_request_id' => $merchant_request_id,
                'checkout_request_id' => $checkout_request_id,
                'amount' => $amount,
                'mpesa_receipt_number' => $mpesa_receipt_number,
                'transaction_date' => $transaction_date,
                'phonenumber' => $phonenumber,
            ];

            if ($stkPush) {
                $stkPush->fill($data)->save();
            } else {
                MpesaSTK::create($data);
            }
        } else {
            $this->failed = true;
        }
        return $this;
    }
}
