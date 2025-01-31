<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Service\OrderController;
use App\Http\Controllers\Api\Service\USAServiceController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/



Route::post('login',  [LoginController::class,'login']);
Route::post('register',  [RegisterController::class,'register']);
Route::post('reset-password',  [RegisterController::class,'reset_password']);
Route::post('logout',  [UserController::class,'logout']);
Route::any('woven/callback',  [UserController::class,'webhook']);


Route::group(['middleware' => ['auth:api', 'Acess']], function () {

    Route::get('get-user',  [UserController::class,'get_user']);
    Route::post('fund-wallet',  [UserController::class,'fund_wallet']);
    Route::get('get-usa-services',  [USAServiceController::class,'get_usa_services']);
    Route::post('order-usa-number',  [USAServiceController::class,'order_usa_number']);
    Route::any('all-orders',  [OrderController::class,'all_orders']);
    Route::post('delete-order',  [OrderController::class,'delete_orders']);





});






Route::any('w-webhook',  [HomeController::class,'world_webhook']);
Route::any('d-webhook',  [HomeController::class,'diasy_webhook']);










Route::any('updatesec',  [HomeController::class,'updatesec']);
Route::any('cancle-sms',  [HomeController::class,'cancle_sms_timer']);


Route::any('user',  [HomeController::class,'user']);



Route::any('e_fund',  [HomeController::class,'e_fund']);
Route::any('e_check',  [HomeController::class,'e_check']);
Route::any('verify',  [HomeController::class,'verify_username']);



