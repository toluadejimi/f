<?php

namespace App\Http\Controllers\Api\Service;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function all_orders(Request $request)
    {
        $orders = Verification::latest()->where('user_id', Auth::id())->take(50)->get()->makeHidden(['expires_in', 'updated_at', 'exp']);

        return response()->json([
            'status' => true,
            'data' => $orders
        ], 200);

    }


    public function delete_orders(Request $request)
    {


        $order = Verification::where('id', $request->order_id)->first() ?? null;

        if($order == null){

            return response()->json([
                'status' => false,
                'message' => "Order not found"
            ], 422);

        }


        if ($order->status == 1 && $order->type == 2) {

            $orderID = $order->order_id;
            $can_order = cancel_world_order($orderID);

            if ($can_order == 0) {
                return response()->json([
                    'status' => false,
                    'message' => "Please try again after some time"
                ], 422);
            }




            if ($can_order == 1) {

                sleep(5);
                $amount = number_format($order->cost, 2);
                Verification::where('id', $request->order_id)->delete();
                User::where('id', Auth::id())->increment('wallet', $order->cost);

                return response()->json([
                    'status' => true,
                    'message' => "Order has been canceled, NGN$amount has been refunded to your wallet"
                ], 200);

            }


            if ($can_order == 3) {
                return response()->json([
                    'status' => false,
                    'message' => "Order has already been canceled"
                ], 422);
            }
        }





        if ($order->status == 1 && $order->type == 1) {

            $order = Verification::where('id', $request->order_id)->first() ?? null;
            if ($order == null) {
                return response()->json([
                    'status' => false,
                    'message' => "Order not found"
                ], 422);
            }

            if ($order->status == 2) {
                Verification::where('id', $request->order_id)->delete();
                return response()->json([
                    'status' => false,
                    'message' => "Order has already been completed"
                ], 422);

            }

            if ($order->status == 1) {

                $orderID = $order->order_id;
                $corder = cancel_order($orderID);

                if ($corder == 0) {

                    return response()->json([
                        'status' => false,
                        'message' => "Please wait and try again later"
                    ], 422);
                }



                if ($corder == 1) {

                    sleep(5);
                    $amount = number_format($order->cost, 2);
                    Verification::where('id', $request->id)->delete();
                    User::where('id', Auth::id())->increment('wallet', $order->cost);

                    return response()->json([
                        'status' => true,
                        'message' => "Order has been canceled, NGN$amount has been refunded to your wallet"
                    ], 200);

                }


            }

        }



    }
}
