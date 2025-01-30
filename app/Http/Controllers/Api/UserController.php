<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VAccount;
use Faker\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    public function get_user(Request $request)
    {

        $user = auth()->user();
        $data['user'] = Auth::user()->makeHidden(['created_at', 'updated_at', 'session_id']);
        $data['token'] = $request->header('Authorization');

        $get_rate_us = Setting::where('id', 1)->first()->get_rate_us;
        $margin_us = Setting::where('id', 1)->first()->margin_us;

        $APIKEY = env('KEY');
        $response = Http::get("https://daisysms.com/stubs/handler_api.php?api_key=$APIKEY&action=getPricesVerification"); // Replace with your API URL
        $data2 = $response->json();
        $allowedServices = ['wa', 'tg', 'fb', 'ig'];
        $filteredData = array_filter($data2, function ($key) use ($allowedServices) {
            return in_array($key, $allowedServices);
        }, ARRAY_FILTER_USE_KEY);


        $images = [
            "fb" => asset("public/assets/images/quick/facebook.svg"),
            "ig" => asset("public/assets/images/quick/instagram.svg"),
            "tg" => asset("public/assets/images/quick/telegram.svg"),
            "wa" => asset("public/assets/images/quick/whatsapp.svg")
        ];

        foreach ($filteredData as $key => &$service) {
            foreach ($service as $id => &$details) {
                $details['image'] = $images[$key] ?? null;
                $details['country'] = "USA";
                $details['cost'] = number_format(($details['cost'] * $get_rate_us) + $margin_us, 2); // Increase cost by $2
            }
        }

        $ck_transactions = Transaction::latest()->where('user_id', Auth::id())->get() ?? null;
        if ($ck_transactions != null) {
            $transactions = Transaction::latest()->where('user_id', Auth::id())->take('10')->get()->makeHidden('updated_at');
        } else {
            $transactions = Transaction::latest()->where('user_id', Auth::id())->take('10')->get();
        }

        $data['quick_services'] = $filteredData;
        $data['transactions'] = $transactions;

        return response()->json([
            'status' => true,
            'data' => $data

        ], 200);

    }


    public function fund_wallet(request $request)
    {

        $faker = Factory::create();
        $first_name = User::inRandomOrder()->first()->first_name;
        $last_name = User::inRandomOrder()->first()->last_name;
        $tremail = $faker->email;
        $phone = User::inRandomOrder()->first()->phone;

        if ($request->amount > 11000) {
            $pamount = $request->amount + 300;
        } else {
            $pamount = $request->amount + 100;

        }

        $amtt = $pamount;


        $code = Setting::where('id', 1)->first()->woven_collective_code;
        $woven_details = woven_create($amtt, $code, $last_name, $tremail, $phone);


        $trx = new VAccount();
        $trx->account_no = $woven_details['account_no'];
        $trx->account_name = $woven_details['account_name'];
        $trx->bank_name = $woven_details['bank_name'];
        $trx->user_id = Auth::id();
        $trx->save();


        return response()->json([
            'status' => true,
            'account_no' => $woven_details['account_no'],
            'account_name' => $woven_details['account_name'],
            'bank_name' => $woven_details['bank_name'],
            'amount_payable' => $pamount,
        ], 200);


    }


    public function logout()
    {
        $user = Auth::user();
        $user->token()->revoke();
        return response()->json(['message' => 'Successfully logged out']);
    }



        public function webhook(Request $request){

        $ip = $request->ip();
        $message = $ip. "====>".json_encode($request->all());
        send_notification($message);


        if($request->ip() != "35.162.80.204"){
            $message = "Wrong IP request | ===>>>".$request->ip();
            send_notification($message);
            return response()->json([
                'status' => false,
                'message' => "Wrong IP request"
            ]);
        }

        $acc_no = $request->nuban;
        $user_amount = $request->amount;
        $session_id = $request->unique_reference;
        $payable = $request->amount_payable;
        $fee = $request->fee;


        $status = VAccount::where('account_no', $acc_no)->first()->status ?? null;
        if ($status == 4) {
            return response()->json([
                'status' => false,
                'message' => "Transaction has already been funded",
            ]);

        }



        $trx = VAccount::where('account_no', $acc_no)
            ->where([
                'status' => 0
            ])->first() ?? null;


        if ($trx == null) {
            return response()->json([
                'status' => false,
                'message' => "Account Not found in our database",
            ]);

        }

        VAccount::where('account_no', $acc_no)->update(['status' => 4]);
        if($user_amount > 11000){
            if ($request->amount > 11000) {
                $ramount = $request->amount - 300;
            } else {
                $ramount = $request->amount - 100;

            }
        }

        $user_id = VAccount::where('account_no', $acc_no)->first()->user_id ?? null;
        if($user_id != null){
            User::where('id', $user_id)->increment('wallet', $ramount);
            $user = User::where('id', $user_id)->first();
            $message = "$ramount has been funded to your main wallet";
            sendDeviceNotification($user->device_id, $message );
            $email = $user->email;
            $amount = $ramount;
            $username = $user->username;
            $subject = "Wallet Funding";
            sendcreditmail($email,$amount,$username,$subject);

        }

            return response()->json([
                'status' => true,
                'message' => "Transaction Successful",
            ]);

    }



}
