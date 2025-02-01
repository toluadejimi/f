<?php

namespace App\Http\Controllers\Api\Service;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorldServiceController extends Controller
{
    public function get_world_country(Request $request)
    {


        $data = get_world_countries();

        return response()->json([
            'status' => true,
            'data' => $data
        ]);


    }

    public function get_world_services(Request $request)
    {
        $data = get_world_services();
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }


    public function check_av(Request $request)
    {

        $get_rate = Setting::where('id', 1)->first()->rate;
        $margin = Setting::where('id', 1)->first()->margin;
        $gcost = pool_cost($request->service, $request->service);
        $ngnprice = ($get_rate * $gcost['cost']) + $margin;

        if ($gcost == 0) {
            return response()->json([
                'status' => false,
                'message' => "Verification not available for selected service"
            ], 422);

        }


        $data['cost'] = $ngnprice;
        $data['success_rate'] = $gcost['success_rate'];
        $data['country_name'] = $request->country_name;
        $data['service'] = $request->service_name;

        return response()->json([
            'status' => true,
            'data' => $data
        ], 200);


    }



    public function order_world_service(Request $request)
    {



        $get_rate = Setting::where('id', 1)->first()->rate;
        $margin = Setting::where('id', 1)->first()->margin;
        $gcost = pool_cost($request->service, $request->service);
        $ngnprice = ($get_rate * $gcost['cost']) + $margin;

        if (Auth::user()->wallet < $ngnprice) {
            return response()->json([
                'status' => true,
                'message' => "Insufficient Funds"
            ], 422);
        }

        $country = $request->country;
        $service = $request->service;
        $price = $ngnprice;
        $country_name = $request->country_name;
        $service_name = $request->service_name;

        $order = create_world_order($country, $service, $price, $country_name, $service_name);

        if ($order == 5) {

            return response()->json([
                'status' => false,
                'message' => "Verification is not available"
            ], 422);

        }


        if ($order == 3) {

            $orders = Verification::latest()->where('user_id', Auth::id())->take(50)->get()->makeHidden(['expires_in', 'updated_at', 'exp']);
            return response()->json([
                'status' => true,
                'data' => $orders
            ], 200);


        }
    }


}
