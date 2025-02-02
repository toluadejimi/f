<?php

use App\Constants\Status;
use App\Models\Extension;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Verification;
use App\Lib\GoogleAuthenticator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


function resolve_complete($order_id)
{

    $curl = curl_init();

    $databody = array('order_id' => "$order_id");

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://web.sprintpay.online/api/resolve-complete',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $databody,
    ));

    $var = curl_exec($curl);
    curl_close($curl);
    $var = json_decode($var);


    $status = $var->status ?? null;
    if ($status == true) {
        return 200;
    } else {
        return 500;
    }
}

function sendcreditmail($email,$amount,$username,$subject )
{


    $data = array(
        'fromsender' => env('MAIL_FROM_ADDRESS'), 'FIGO SMS',
        'subject' => $subject,
        'toreceiver' => $email,
        'user' => $username,
        'amount' => $amount,
    );

    Mail::send('credit-mail', ["data1" => $data], function ($message) use ($data) {
        $message->from($data['fromsender']);
        $message->to($data['toreceiver']);
        $message->subject($data['subject']);
    });




}

function sendNotificationToHttp(array|null $data): bool|string|null
{

    $get_key = Setting::where('key_name','server_key')->first()->value;
    $key = json_decode($get_key, false);

    $url = 'https://fcm.googleapis.com/v1/projects/' . $key->project_id . '/messages:send';
    $headers = [
        'Authorization' => 'Bearer ' . getAccessToken($key),
        'Content-Type' => 'application/json',
    ];
    try {
        return Http::withHeaders($headers)->post($url, $data);


    } catch (\Exception $exception) {
        return false;
    }
}


function sendDeviceNotification($device_id, $message, $title)
{


    $postData = [
        'message' => [
            'token' => $device_id,
            'data' => [

                "message" => "$message",
                "sound" => "notification.wav",
                "android_channel_id" => "hexa-ride"
            ],
            'notification' => [
                "title" => $title,
                "body" => $message,
            ]
        ]
    ];

    return sendNotificationToHttp($postData);

}


function getAccessToken($key): string
{
    $jwtToken = [
        'iss' => $key->client_email,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => time() + 3600,
        'iat' => time(),
    ];
    $jwtHeader = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $jwtPayload = base64_encode(json_encode($jwtToken));
    $unsignedJwt = $jwtHeader . '.' . $jwtPayload;
    openssl_sign($unsignedJwt, $signature, $key->private_key, OPENSSL_ALGO_SHA256);
    $jwt = $unsignedJwt . '.' . base64_encode($signature);

    $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt,
    ]);
    return $response->json('access_token');
}


function woven_create($amtt, $code, $last_name, $tremail, $phone)
{


    if($code == "090110"){
        $bank_name = "VFD";
    }elseif ($code == "000017"){
        $bank_name = "WEMA";
    }else{
        $bank_name = "CORONATION MERVHANT BANK";
    }


    $key = env('WOVENKEY');
    $databody = array(
        "amount" => $amtt,
        "collection_bank" => $code,
        "callback_url" => url('') . "/api/woven/callback",

    );

    $post_data = json_encode($databody);
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.woven.finance/v2/api/nuban/dynamic',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            "api_secret: $key"
        ),
    ));

    $var = curl_exec($curl);
    curl_close($curl);
    $var = json_decode($var);
    $status = $var->message ?? null;



    if ($status == "The process was completed successfully") {
        $data['account_no'] = $var->data->vnuban;
        $data['bank_name'] = $bank_name;
        $data['account_name'] = "WOV CHECKOUT";
        return $data;
    }


}

function send_notification($message)
{

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => '#',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'chat_id' => "1316552414",
                'text' => $message,
            ),
            CURLOPT_HTTPHEADER => array(),
        ));

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);
}

function send_notification2($message)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => '#',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'chat_id' => "7109127373",
                'text' => $message,

            ),
            CURLOPT_HTTPHEADER => array(),
        ));

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);
    }

function session_resolve($session_id, $ref){

    $curl = curl_init();

    $databody = array(
        'session_id' => "$session_id",
        'ref' => "$ref"
    );


    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://web.sprintpay.online/api/resolve',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $databody,
    ));

    $var = curl_exec($curl);
    curl_close($curl);
    $var = json_decode($var);

    $message = $var->message ?? null;
    $status = $var->status ?? null;

    $amount = $var->amount ?? null;

    return array([
        'status' => $status,
        'amount' => $amount,
        'message' => $message
    ]);


}

function get_services(){

    $APIKEY = env('KEY');

    $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://daisysms.com/stubs/handler_api.php?api_key=$APIKEY&action=getPricesVerification",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json',
            ),
        ));

        $var = curl_exec($curl);

        curl_close($curl);
        $var = json_decode($var);


        $services = $var ?? null;

        if ($var == null) {
            $services = null;
        }

        return $services;

}

function create_order($service, $price, $cost, $service_name, $costs){



    $APIKEY = env('KEY');
    $curl = curl_init();

   curl_setopt_array($curl, array(
       CURLOPT_URL => "https://daisysms.com/stubs/handler_api.php?api_key=$APIKEY&action=getNumber&service=$service&max_price=$cost",
       CURLOPT_RETURNTRANSFER => true,
       CURLOPT_ENCODING => '',
       CURLOPT_MAXREDIRS => 10,
       CURLOPT_TIMEOUT => 0,
       CURLOPT_FOLLOWLOCATION => true,
       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
       CURLOPT_CUSTOMREQUEST => 'GET',
   ));

   $var = curl_exec($curl);
   curl_close($curl);
   $result = $var ??  null;

    if(strstr($result, "ACCESS_NUMBER") !== false) {


        $parts = explode(":", $result);
        $accessNumber = $parts[0];
        $id = $parts[1];
        $phone = $parts[2];


        Verification::where('phone', $phone)->where('status', 2)->delete() ?? null;

        $ver = new Verification();
        $ver->user_id = Auth::id();
        $ver->phone = $phone;
        $ver->order_id = $id;
        $ver->country = "USA";
        $ver->service = $service_name;
        $ver->cost = $price;
        $ver->api_cost = $cost;
        $ver->status = 0;
        $ver->type = 1;
        $ver->save();


        User::where('id', Auth::id())->decrement('wallet', $costs);

        $data['id'] = $ver->id;
        $data['status'] = 1;
        return $data;

    }elseif($result == "MAX_PRICE_EXCEEDED" || $result == "NO_NUMBERS" || $result == "TOO_MANY_ACTIVE_RENTALS" || $result == "NO_MONEY") {
        $data['status'] = 0;
        return $data;
    }else{
        $data['status'] = 0;
        return $data;
    }




}

function cancel_order($orderID){


   $APIKEY = env('KEY');
   $curl = curl_init();

   curl_setopt_array($curl, array(
       CURLOPT_URL => "https://daisysms.com/stubs/handler_api.php?api_key=$APIKEY&action=setStatus&id=$orderID&status=8",
       CURLOPT_RETURNTRANSFER => true,
       CURLOPT_ENCODING => '',
       CURLOPT_MAXREDIRS => 10,
       CURLOPT_TIMEOUT => 0,
       CURLOPT_FOLLOWLOCATION => true,
       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
       CURLOPT_CUSTOMREQUEST => 'GET',
   ));

    $var = curl_exec($curl);
    curl_close($curl);
    $result = $var ?? null;

    if(strstr($result, "ACCESS_CANCEL") !== false){
        return 1;
    }else{
        return 0;
    }




}

function check_sms($orderID){

   $APIKEY = env('KEY');
   $curl = curl_init();

   curl_setopt_array($curl, array(
       CURLOPT_URL => "https://daisysms.com/stubs/handler_api.php?api_key=$APIKEY&action=getStatus&id=$orderID",
       CURLOPT_RETURNTRANSFER => true,
       CURLOPT_ENCODING => '',
       CURLOPT_MAXREDIRS => 10,
       CURLOPT_TIMEOUT => 0,
       CURLOPT_FOLLOWLOCATION => true,
       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
       CURLOPT_CUSTOMREQUEST => 'GET',
   ));

    $var = curl_exec($curl);
    curl_close($curl);
    $result = $var ?? null;

    if(strstr($result, "NO_ACTIVATION") !== false){

        return 1;

    }

    if(strstr($result, "NO_ACTIVATION") !== false){

        return 1;

    }

    if(strstr($result, "STATUS_WAIT_CODE") !== false){

        return 2;

    }

    if(strstr($result, "STATUS_CANCEL") !== false){

        return 4;

    }




    if(strstr($result, "STATUS_OK") !== false) {


        $status = Verification::where('order_id', $orderID)->first()->status ?? null;
        if($status != 2){
            $parts = explode(":", $result);
            $text = $parts[0];
            $sms = $parts[1];

            $data['sms'] = $sms;
            $data['full_sms'] = $sms;

            Verification::where('order_id', $orderID)->update([
                'status' => 2,
                'sms' => $sms,
                'full_sms' => $sms,
            ]);

            try{

                $order = Verification::where('order_id', $orderID)->first() ?? null;
                $user_id = Verification::where('order_id', $orderID)->first()->user_id ?? null;
                User::where('id', $user_id)->decrement('hold_wallet', $order->cost);

            }catch (\Exception $e) {
                $message = $e->getMessage();
                send_notification($message);
                send_notification2($message);
            }

            $message = "$orderID | completed";
            send_notification($message);


            return 3;

        }



    }


}



function get_world_countries(){

    $key = env('WKEY');

    $countries = Cache::remember('smspool_countries', 3600, function () use ($key) {
        Log::info('Requesting countriees from SMS Pool API', ['key' => $key]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(50)
            ->post("https://api.smspool.net/country/retrieve_all", [
                'key' => $key,
            ]);

        Log::info('Response received from SMS Pool API', ['response' => $response->body()]);

        if ($response->successful()) {
            return $response->json() ?? null;
        }

        Log::error('API call failed', ['response' => $response->body()]);
        return null;
    });




    return $countries;




}


function get_world_services(){


    $key = env('WKEY');

    $services = Cache::remember('smspool_services', 3600, function () use ($key) {
        Log::info('Requesting services from SMS Pool API', ['key' => $key]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(50)
            ->post("https://api.smspool.net/service/retrieve_all", [
                'key' => $key,
            ]);

        Log::info('Response received from SMS Pool API', ['response' => $response->body()]);

        if ($response->successful()) {
            return $response->json() ?? null;
        }

        Log::error('API call failed', ['response' => $response->body()]);
        return null;
    });



    return $services;




}


function create_world_order($country, $service, $price, $country_name, $service_name){


    $key = env('WKEY');
    $curl = curl_init();

    $databody = array(
        'country' => $country,
        'service' => $service,
        'key' => $key,


    );
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.smspool.net/purchase/sms',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $databody,
    ));

    $var = curl_exec($curl);
    curl_close($curl);
    $var = json_decode($var);


    $success = $var->success ?? null;


    if($success == 0){
        return 5;

    }



    if($success == 1){

        Verification::where('phone', $var->cc.$var->phonenumber)->where('status', 2)->delete() ?? null;
        $currentTime = Carbon::now();
        $futureTime = $currentTime->addMinutes(15);
        $formattedTime = $futureTime->format('Y-m-d H:i:s');

        $ver = new Verification();
        $ver->user_id = Auth::id();
        $ver->phone = $var->cc.$var->phonenumber;
        $ver->order_id = $var->order_id;
        $ver->country = $var->country;
        $ver->service = $var->service;
        $ver->cost = $price;
        $ver->api_cost = $var->cost;
        $ver->status = 1;
        $ver->type = 2;

        $ver->save();

        User::where('id', Auth::id())->decrement('wallet', $price);

        return 3;


    }








    $status = $var->type ?? null;

    if($status == "BALANCE_ERROR"){
        return  1;
    }

    if($status == null){
        return 2;

    }




}

function cancel_world_order($orderID){

    $key = env('WKEY');
    $curl = curl_init();

    $databody = array(
        'orderid' => $orderID,
        'key' => $key,
    );
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.smspool.net/sms/cancel',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $databody,
    ));

    $var = curl_exec($curl);
    curl_close($curl);
    $var = json_decode($var);


    $status = $var->success ?? null;
    $message = $var->message ?? null;

    if($status == 0 && $message == "We could not find this order!"){
        return 3;
    }

    if($status == 0 && $message == "Your order cannot be cancelled yet, please try again later."){
        return 0;
    }


    if($status == 1){
        return 1;
    }




}

function check_world_sms($orderID){

    $key = env('KEY');
    $curl = curl_init();

    $databody = array(
        'orderid' => $orderID,
        'key' => $key,
    );
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.smspool.net/sms/check',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $databody,
    ));

    $var = curl_exec($curl);
    curl_close($curl);
    $var = json_decode($var);

    $status = $var->status ?? null;
    $sms = $var->sms ?? null;
    $full_sms = $var->full_sms ?? null;


    if($status == 1){

        Verification::where('order_id', $orderID)->update([
            'expires_in' => $var->time_left / 10 - 20,
        ]);

        return 1;
    }

    if($status == 6){
        return 6;
    }


    if($status == 3){

        $data['sms'] = $sms;
        $data['full_sms'] = $full_sms;

        Verification::where('order_id', $orderID)->update([
            'status' => 2,
            'sms' => $sms,
            'full_sms' => $full_sms,
        ]);

        return 3;
    }



    dd($var);
}


function get_title($service, $country)
{

    $key = env('WKEY');
    $databody = array(
        "country" => $country,
        "service" => $service,
        "web" => '1',
    );

    $body = json_encode($databody);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.smspool.net/pool/retrieve_valid',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $databody,
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer $key"
        ),
    ));

    $var = curl_exec($curl);
    curl_close($curl);
    $var = json_decode($var);

    dd($var);



}


function pool_cost($service, $country){

    $key = env('WKEY');
    $databody = array(
        "key" => $key,
        "country" => $country,
        "service" => $service,
        "pool" => '',
    );

    $body = json_encode($databody);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.smspool.net/request/price',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $databody,
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer $key"
        ),
    ));

    $var = curl_exec($curl);
    curl_close($curl);
    $var = json_decode($var);

    $get_s_price = $var->price ?? null;
    $high_price = $var->high_price ?? null;
    $rate = $var->success_rate ?? null;

    if($rate == null ){
        return 0;
    }

    if($get_s_price < 4){
        $price = $get_s_price * 1.3;
    }else{
        $price = $get_s_price;
    }

    $data['cost']  = $price;
    $data['success_rate']  = $rate;

    return $data;


}

function get_d_price($service){
    $APIKEY = env('KEY');
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://daisysms.com/stubs/handler_api.php?api_key=$APIKEY&action=getPrices&service=$service",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Accept: application/json',
        ),
    ));

    $var = curl_exec($curl);
    curl_close($curl);
    $var = json_decode($var);



    foreach($var as $key => $value){
        $service2['data'] =  $value;
    }


    $data['cost'] = $service2["data"]->$service->cost;
    $data['name'] = $service2["data"]->$service->name;


    return $data;

}




function send_verification_email($email)
{


    try{

        $expiryTimestamp = time() + 24 * 60 * 60; // 24 hours in seconds
        $url = url('') . "/verify-account-now?code=$expiryTimestamp&email=$email";
        $username = User::where('email', $request->email)->first()->username ?? null;

        User::where('email', $email)->update([
            'code' => $expiryTimestamp
        ]);

        $data = array(
            'fromsender' => 'noreply@acesmsverify.com', 'ACEVERIFY',
            'subject' => "Verify Account",
            'toreceiver' => $email,
            'url' => $url,
            'user' => $username,
        );


        Mail::send('verify-account', ["data1" => $data], function ($message) use ($data) {
            $message->from($data['fromsender']);
            $message->to(Auth::user()->email);
            $message->subject($data['subject']);
        });


        return 1;

    }catch (Exception $e) {

        return 0;
    }


}
