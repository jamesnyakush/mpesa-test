<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaB2CController extends Controller
{
//    public function simulate(Request $request)
//    {
//        $phoneno = $request->input('phonenumber');
//        $amount = $request->input('amount');
//        $remarks = $request->input('remarks');
//        $command = $request->input('command');
//
//
//        $response = Mpesa::b2c($phoneno, $command, $amount, $remarks);
//
//        $result = $response->json();
//
//        return response([
//            'message' => 'Success',
//            'data' => $result
//        ], 200);
//
//    }
//
//    public function result(Request $request)
//    {
//        $b2c_confirm = (new B2C())->results($request);
//
//        if ($b2c_confirm) {
//            return response([
//                'message' => 'Withdrawal Successful'
//            ], 200);
//        }
//    }
//
//    public function timeout(Request $request)
//    {
//        Log::info("Timeout URL has been hit");
//        Log::info($request->all());
//    }
}
