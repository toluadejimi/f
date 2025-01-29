<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'password' => 'required|string|min:4|confirmed',
        ]);


        $check_email = User::where('email', $request->email)->where('verify', 2)->first()->email ?? null;
        if ($check_email != null) {
            return response()->json([
                'status' => false,
                'message' => "Email has been taken"
            ], 401);
        }

        $check_username = User::where('email', $request->username)->where('verify', 2)->first()->username ?? null;
        if ($check_username != null) {
            return response()->json([
                'status' => false,
                'message' => "Username has been taken"
            ], 401);
        }


        User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($validatedData['password']),
        ]);


        $email = $request->email;
        $expiryTimestamp = time() + 24 * 60 * 60; // 24 hours in seconds
        $url = url('') . "/register-email?code=$expiryTimestamp&email=$request->email";
        $ck = User::where('email', $request->email)->first()->email ?? null;
        $username = User::where('email', $request->email)->first()->username ?? null;
        if ($ck == $request->email) {

            User::where('email', $email)->update([
                'code' => $expiryTimestamp
            ]);

            $data = array(
                'fromsender' => 'noreply@enkpay.com', 'FIGO SMS',
                'subject' => "New Registration ",
                'toreceiver' => $email,
                'url' => $url,
                'user' => $username,
            );

            Mail::send('verify-account', ["data1" => $data], function ($message) use ($data) {
                $message->from($data['fromsender']);
                $message->to($data['toreceiver']);
                $message->subject($data['subject']);
            });


        }

        return response()->json([
            'status' => true,
            'message' => "Verification mail has been sent to your Email"
        ],200);



    }


    public function register_email(request $request)
    {

        $storedExpiryTimestamp = $request->code;;
        if (time() >= $storedExpiryTimestamp) {
            $user = Auth::id() ?? null;
            $email = $request->email;
            return view('expired', compact('user', 'email'));
        } else {

            $user = Auth::id() ?? null;
            $email = $request->email;
            User::where('email', $request->email)->update(['verify' => 2]);

            return view('registration-success', compact('user', 'email'));
        }
    }


    public function reset_password(Request $request)
    {

        $email = $request->email;
        $expiryTimestamp = time() + 24 * 60 * 60; // 24 hours in seconds
        $url = url('') . "/verify-password?code=$expiryTimestamp&email=$request->email";
        $ck = User::where('email', $request->email)->first()->email ?? null;
        $username = User::where('email', $request->email)->first()->username ?? null;
        if ($ck == $request->email) {

            User::where('email', $email)->update([
                'code' => $expiryTimestamp
            ]);

            $data = array(
                'fromsender' => 'noreply@enkpay.com', 'FIGO SMS',
                'subject' => "Reset Password ",
                'toreceiver' => $email,
                'url' => $url,
                'user' => $username,
            );

            Mail::send('verify-account', ["data1" => $data], function ($message) use ($data) {
                $message->from($data['fromsender']);
                $message->to($data['toreceiver']);
                $message->subject($data['subject']);
            });


        }else{
            return response()->json([
                'status' => false,
                'message' => "Account not found on our system"
            ],401);

        }

        return response()->json([
            'status' => true,
            'message' => "Reset mail has been sent to your Email"
        ],200);



    }



}
