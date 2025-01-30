<?php

namespace App\Http\Controllers\Api\Service;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class USAServiceController extends Controller
{
    public function get_usa_services(Request $request)
    {

        $setting = Setting::where('id', 1)->firstOrFail();
        $get_rate_us = $setting->get_rate_us;
        $margin_us = $setting->margin_us;

        $data['services'] = get_services();

        foreach ($data['services'] as $key => &$service) {
            foreach ($service as $id => &$details) {
                if (is_object($details)) {
                    $details = (array)$details;
                }
                $details['country'] = "USA";
                $details['cost'] = number_format(($details['cost'] * $get_rate_us) + $margin_us, 2);
            }
        }

        return response()->json([
            'status' => true,
            'data' => $data
        ]);


    }


    public function order_usa_number(Request $request)
    {

        $setting = Setting::where('id', 1)->firstOrFail();
        $get_rate_us = $setting->get_rate_us;
        $margin_us = $setting->margin_us;


        $service = $request->service;
        $gcost = get_d_price($service);

        $costs = ($get_rate_us * $gcost['cost']) + $margin_us;



        if($request->wallet == "main_wallet"){
            $wallet = Auth::user()->wallet;
        }else{
            $wallet = Auth::user()->bonus_wallet;
        }


        if ($wallet < $costs) {

            return response()->json([
                'status' => false,
                'message' => "Insufficient Funds"
            ],422);

        }


        $service = $request->service;
        $price = $costs;
        $cost = $gcost['cost'];
        $service_name = $gcost['name'];



        $order = create_order($service, $price, $cost, $service_name, $costs);

        if ($order['status'] == 0) {
            return redirect('home')->with('error', 'Number Currently out of stock, Please check back later');
        }

        if ($order['status'] == 1) {

            $order = Verification::where('id', $order['id'])->first() ?? null;
            $image = Image::where('id', $order->service)->first()->url ?? null;

            $data['service'] = $order->service;
            $data['country'] = "USA";
            $data['status'] = $order->status;
            $data['image'] = $image;






            return response()->json([
                'status' => true,
                'message' => "Insufficient Funds"
            ],200);

        }


        if ($order['status'] == 1) {
            return redirect('orders');
        }
    }


}
