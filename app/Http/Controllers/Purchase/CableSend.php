<?php
namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Cable;
use App\Models\AdexAPI;
use App\Models\CablePlan;
use App\Models\CableID;
use App\Models\OtherApi;
use App\Models\General;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\PurchaseNotification;
use Illuminate\Support\Facades\Http;

class  CableSend extends Controller {

public static function adexHandler(Cable $data, CablePlan $cable_plan)
{
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
    foreach ($cable_plan->api_mappings as $map) {
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
    $cable_id = CableID::where('cable_name', strtolower($data->cable_name))->first();
    $data->request_time = now();
    $data->vending_url = $AdexAPI->website_url . "/api/cable";
    $data->save();
    $payload = array(
         'cable' => $cable_id->plan_id,
         'iuc' => $data->iuc,
         'cable_plan' => $adexPlanId,
         'bypass' => true,
         'request-id' => $data->my_trans_id
    );
    $PResponse = Http::withHeaders([
        "Authorization" => "Token $access_token",
        "Content-Type" => "application/json"
    ])->post($data->vending_url, $payload);

    $data->response_time = now();
    $data->ServerResponse = $PResponse->body();
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
public function quickly(Cable $data, CablePlan $cable_plan){
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
    $data->vending_url = "https://quicklysim.com/api/cable";
    $data->save();
    $cable_id = CableID::where('cable_name', strtolower($data->cable_name))->first();
    $payload = array(
         'cable' => $cable_id->plan_id,
         'iuc' => $data->iuc,
         'cable_plan' => $cable_plan->quickly,
         'bypass' => true,
         'request-id' => $data->my_trans_id
    );
    $PResponse = Http::withHeaders([
        "Authorization" => "Token $access_token",
        "Content-Type" => "application/json"
    ])->post($data->vending_url, $payload);
    $data->response_time = now();
    $data->ServerResponse = $PResponse->body();
    $data->save();
    if ($PResponse->successful() && $PResponse->json('status') === 'success') {
        return 'success';
    } elseif ($PResponse->json('status') === 'fail') {
        return 'fail';
    } elseif ($PResponse->json('status') === 'process') {
        return 'process';
    }
}
public static function vtpass(Cable $data, CablePlan $cable_plan){
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
        'serviceID' => strtolower($data->cable_name == 'startime' ? 'startimes' : $data->cable_name),
        'billersCode' => $data->iuc,
        'variation_code' => $cable_plan->vtpass,
        'phone' => auth()->user()->phone,
        'request_id' => $reference
    ];
    $headers = [
        'Authorization' => 'Basic ' . base64_encode($api->vtpass_username . ':' . $api->vtpass_password),
        'Content-Type' => 'application/json'
    ];
    $response = Http::withHeaders($headers)->timeout(120)->post($data->vending_url, $payload);
    $data->response_time = now();
    $data->ServerResponse = $response->body();
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
public static function gmail(Cable $data, CablePlan $cableplan)
{
    if ($data->plan_status != 0 || is_null($data->vending_system)) {
        return 'process';
    }
    $data->request_time = now();
    $data->vending_url = 'gmail.com';
    $data->save();
    $adminUrl = url('/secure/ade-2025/internal/panel/cable-transactions/' . $data->id . '/edit');
    $message = strtoupper($data->username) . ' wants to buy ' . $cableplan->cable_name . ' ' . $data->cable_plan . ' ₦' . number_format($data->amount, 2) . ' to ' . $data->iuc . '. Reference is ' . $data->my_trans_id;
    $adminEmails = User::where(['status' => 1, 'type' => 'ADMIN'])->pluck('email')->toArray();
    $sets = General::first();
    $email_data = [
        'username' => $data->username,
        'title' => 'New Cable Purchase Request',
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
}
