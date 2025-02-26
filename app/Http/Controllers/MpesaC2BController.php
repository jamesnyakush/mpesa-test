<?php

namespace App\Http\Controllers;

use App\Mpesa\C2B;
use Iankumu\Mpesa\Facades\Mpesa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaC2BController extends Controller
{

    public function simulate(Request $request)
    {
        $phonenumber = $request->input('phonenumber');
        $amount = $request->input('amount');
        $account = $request->input('account');
        $shortcode = $request->input('shortcode');
        $command = $request->input('command');

        if ($command == "CustomerPayBillOnline") {
            $response = Mpesa::c2bsimulate($phonenumber, $amount, $shortcode, $command, $account);
        } else {
            $response = Mpesa::c2bsimulate($phonenumber, $amount, $shortcode, $command);
        }

        return json_decode((string)$response, true);
    }

    public function registerURLS(Request $request)
    {
        $shortcode = $request->input('shortcode');
        $response = Mpesa::c2bregisterURLS($shortcode);

        return $response->json();
    }

    public function validation()
    {
        Log::info('Validation endpoint has been hit');
        $result_code = "0";
        $result_description = "Accepted validation request";
        return Mpesa::validationResponse($result_code, $result_description);
    }

    public function confirmation(Request $request)
    {

        return (new C2B())->confirm($request);
    }
}
