<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\OauthAccessToken;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;

class LoginController extends Controller
{


    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (auth()->attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = auth()->user();
            $token = $user->createToken('API Token')->accessToken;
            $data['user'] = Auth::user()->makeHidden(['created_at', 'updated_at', 'session_id']);
            $data['token'] = $token;

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
                "fb" => asset("images/quick/facebook.svg"),
                "ig" => asset("images/quick/instagram.svg"),
                "tg" => asset("images/quick/telegram.svg"),
                "wa" => asset("images/quick/whatsapp.svg")
            ];

            foreach ($filteredData as $key => &$service) {
                foreach ($service as $id => &$details) {
                    $details['image'] = $images[$key] ?? null;
                    $details['country'] = "USA";
                    $details['cost'] = number_format(($details['cost'] * $get_rate_us) + $margin_us , 2); // Increase cost by $2
                }
            }

            $transactions = Transaction::latest()->where('user_id', Auth::id())->take('10')->get()->makeHidden('updated_at');
            $data['quick_services'] = $filteredData;
            $data['transactions'] = $transactions;

            return response()->json(['data' => $data], 200);

        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }



}
