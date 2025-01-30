<?php

namespace App\Http\Controllers\Api\Service;

use App\Http\Controllers\Controller;
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

        $data['get_rate'] = Setting::where('id', 1)->first()->rate;
        $data['margin'] = Setting::where('id', 1)->first()->margin;


        $service = $request->service;

        $gcost = get_d_price($service);

        $costs = ($data['get_rate'] * $gcost) + $data['margin'];
        if (Auth::user()->wallet < $costs) {
            return back()->with('error', "Insufficient Funds");
        }


        $service = $request->service;
        $price = $request->price;
        $cost = $request->cost;
        $service_name = $request->name;

        $order = create_order($service, $price, $cost, $service_name, $costs);
        if ($order == 8) {
            return back()->with('error', "Insufficient Funds");
        }

        if ($order == 7) {
            return back()->with('error', "Kindly Fund your wallet");
        }

        if ($order == 8) {
            return back()->with('error', "Insufficient Funds");
        }

        if ($order == 8) {
            return back()->with('error', "Insufficient Funds");
        }


        //dd($order);

        if ($order == 9) {

            $ver = Verification::where('status', 1)->first() ?? null;
            if ($ver != null) {
                return redirect('us');
            }
            return redirect('us');
        }

        if ($order == 0) {
            return redirect('home')->with('error', 'Number Currently out of stock, Please check back later');
        }


        if ($order == 1) {
            return redirect('orders');
        }
    }


}
