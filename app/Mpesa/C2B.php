<?php

namespace App\Mpesa;

use App\Models\MpesaC2B;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class C2B
{
    public function confirm(Request $request): array
    {
        Log::info('Confirmation endpoint has been hit');
        $payload = $request->all();

        $c2b = new MpesaC2B();
        $c2b->transaction_type = $payload['TransactionType'];
        $c2b->mpesa_receipt_number = $payload['TransID'];
        $c2b->transaction_date = $payload['TransTime'];
        $c2b->amount = $payload['TransAmount'];
        $c2b->business_shortcode = $payload['BusinessShortCode'];
        $c2b->account_number = $payload['BillRefNumber'];
        $c2b->invoice_no = $payload['InvoiceNumber'];
        $c2b->organization_account_balance = $payload['OrgAccountBalance'];
        $c2b->third_party_transaction_id = $payload['ThirdPartyTransID'];
        $c2b->phone_number = $payload['MSISDN'];
        $c2b->firstname = $payload['FirstName'];
        $c2b->save();

        return $payload;
    }
}
