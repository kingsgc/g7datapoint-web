<?php
namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Bill;
use App\Models\BillPlan;
use App\Models\AdexAPI;
use App\Models\OtherApi;
use App\Models\General;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\PurchaseNotification;
use Illuminate\Support\Facades\Http;

class  BillSend extends Controller {
public static function adexHandler(Bill $data, BillPlan $bill_plan){
    if ($data->plan_status != 0 || is_null($data->vending_system)) {
        return 'process';
    }
    $adexID = str_replace('adex', '', $data->vending_system);
    if (empty($adexID)) {
        return 'process';
    }
    $AdexAPI = AdexAPI::find($adexID);
    if (empty($AdexAPI) || is_null($AdexAPI->website_url) || is_null($AdexAPI->username) || is_null($AdexAPI->password)) {
        $data->ServerResponse = 'Missing Adex API credentials or URL';
        $data->save();
        return 'fail';
    }
    $accessToken = base64_encode($AdexAPI->username . ":" . $AdexAPI->password);
    $response = Http::withHeaders(["Authorization" => "Basic " . $accessToken])
        ->post($AdexAPI->website_url . '/api/user');
    if (!$response->successful() || empty($response->json('AccessToken'))) {
        $data->ServerResponse = $response->body() ?: 'Failed to retrieve access token';
        $data->save();
        return 'fail';
    }
    $access_token = $response->json('AccessToken');
    $adexPlanId = null;
    foreach ($bill_plan->api_mappings as $map) {
        if ($map['adex_api_id'] == $AdexAPI->id) {
            $adexPlanId = $map['plan_id'];
            break;
        }
    }
    if (empty($adexPlanId)) {
        $data->ServerResponse = 'No plan mapping found for AdexAPI ID ' . $AdexAPI->id;
        $data->save();
        return 'fail';
    }
    $data->request_time = now();
    $data->vending_url = $AdexAPI->website_url . "/api/bill";
    $data->save();
    $payload = array( 
         'disco' => $adexPlanId,
         'meter_number' => $data->meter_number,
         'meter_type' => strtolower($data->meter_type),
         'amount' => $data->amount,
         'bypass' => true,
         'request-id' => $data->my_trans_id
    );
    $PResponse = Http::withHeaders([
        "Authorization" => "Token $access_token",
        "Content-Type" => "application/json"
    ])->post($data->vending_url, $payload);
    $data->response_time = now();
    $data->ServerResponse = $PResponse->body();
    $data->token = $PResponse->json('token');
    $data->save();
    if ($PResponse->successful() && $PResponse->json('status') === 'success') {
        return 'success';
    } elseif ($PResponse->json('status') === 'fail') {
        return 'fail';
    } elseif ($PResponse->json('status') === 'process') {
        return 'process';
    }
    return 'fail';
}
public function quickly(Bill $data, BillPlan $bill_plan){
     if ($data->plan_status != 0 || is_null($data->vending_system)) {
            return 'process';
        }
        $quicklySim = OtherApi::first();
        if (is_null($quicklySim) || is_null($quicklySim->quickly_email) || is_null($quicklySim->quickly_pass)) {
            $data->ServerResponse = 'Missing Quicklsim API credentials';
            $data->save();
            return 'fail';
        }
     $accessToken = base64_encode($quicklySim->quickly_email . ":" . $quicklySim->quickly_pass);
    $response = Http::withHeaders(["Authorization" => "Basic " . $accessToken])
        ->post('https://quicklysim.com/api/user');
    if (!$response->successful() || empty($response->json('AccessToken'))) {
        $data->ServerResponse = $response->body() ?: 'Failed to retrieve access token';
        $data->save();
        return 'fail';
    }
    $access_token = $response->json('AccessToken');
    $data->request_time = now();
    $data->vending_url = "https://quicklysim.com/api/bill";
    $data->save();
    $payload = array( 
         'disco' => $bill_plan->quickly,
         'meter_number' => $data->meter_number,
         'meter_type' => strtolower($data->meter_type),
         'amount' => $data->amount,
         'bypass' => true,
         'request-id' => $data->my_trans_id
    );
    $PResponse = Http::withHeaders([
        "Authorization" => "Token $access_token",
        "Content-Type" => "application/json"
    ])->post($data->vending_url, $payload);
    $data->response_time = now();
    $data->ServerResponse = $PResponse->body();
    $data->token = $response->json('token');
    $data->save();
    if ($PResponse->successful() && $PResponse->json('status') === 'success') {
        return 'success';
    } elseif ($PResponse->json('status') === 'fail') {
        return 'fail';
    } elseif ($PResponse->json('status') === 'process') {
        return 'process';
    }
    return 'fail';
}
public static function gmail(Bill $data, BillPlan $billPlan)
{
    if ($data->plan_status != 0 || is_null($data->vending_system)) {
        return 'process';
    }
    $data->request_time = now();
    $data->vending_url = 'gmail.com';
    $data->save();
    $adminUrl = url('/secure/ade-2025/internal/panel/bill-transactions/' . $data->id . '/edit');
    $message = strtoupper($data->username) . ' wants to buy ' . $data->disco_name . ' ' . $data->cable_plan . ' ₦' . number_format($data->amount, 2) . ' to ' . $data->meter_number . '. Reference is ' . $data->my_trans_id;
    $adminEmails = User::where(['status' => 1, 'type' => 'ADMIN'])->pluck('email')->toArray();
    $sets = General::first();
    $email_data = [
        'username' => $data->username,
        'title' => 'New Bill Purchase Request',
        'sender_mail' => $sets->app_email,
        'app_name' => $sets->app_name,
        'mes' => $message,
        'action_url' => $adminUrl, // Add the action URL
        'action_text' => 'View Transaction', // Button text
    ];
    try{
     foreach ($adminEmails as $adminEmail) {
        Mail::to($adminEmail)->queue(new PurchaseNotification($email_data));
    }
        $data->response_time = now();
        $data->save();
        return 'success';
    } catch (\Exception $e) {
        $data->ServerResponse = 'Email sending failed: ' . $e->getMessage();
        $data->response_time = now();
        $data->save();
        return 'fail';
    }
}

public static function vtpass(Bill $data, BillPlan $bill_plan){
    if ($data->plan_status != 0 || is_null($data->vending_system)) {
        return 'process';
    }
    $api = OtherApi::first();
    if (is_null($api) || is_null($api->vtpass_username) || is_null($api->vtpass_password)) {
        $data->ServerResponse = 'Missing VTpass API credentials';
        $data->save();
        return 'fail';
    }
    $data->request_time = now();
    $data->vending_url = 'https://vtpass.com/api/pay';
    $data->save();
    $reference = now()->format('YmdHis') . '_' . $data->my_trans_id;
    $payload = [
        'serviceID' => $bill_plan->vtpass,
        'billersCode' => $data->meter_number,
        'variation_code' => strtolower($data->meter_type),
        'phone' => auth()->user()->phone,
        'request_id' => $reference,
        'amount' => $data->amount
    ];
    $headers = [
        'Authorization' => 'Basic ' . base64_encode($api->vtpass_username . ':' . $api->vtpass_password),
        'Content-Type' => 'application/json'
    ];
    $response = Http::withHeaders($headers)->timeout(120)->post($data->vending_url, $payload);
    $data->response_time = now();
    $data->ServerResponse = $response->body();
    $data->token = $response->json('purchased_code');
    $data->save();
    if ($response->successful()) {
        $code = $response->json('code');
        $desc = $response->json('response_description');
        if ($code === '000') {
            return 'success';
        } elseif ($desc !== 'TRANSACTION SUCCESSFUL') {
            return 'fail';
        } else {
            return 'process';
        }
    } else {
        return 'fail';
    }
}


}
