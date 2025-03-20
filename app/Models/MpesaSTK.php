<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MpesaSTK extends Model
{
    protected $guarded = [];

    protected $table = 'mpesa_stk';

    protected $fillable = [
        'merchant_request_id',
        'checkout_request_id',
        'result_desc',
        'result_code',
        'amount',
        'mpesa_receipt_number',
        'transaction_date',
        'phonenumber',
    ];

}
