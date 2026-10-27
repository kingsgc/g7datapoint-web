<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\AdexAPI;
use App\Models\OtherApi;
use Illuminate\Support\Facades\Http;
use App\Models\BillPlan;

class  MeterSend extends Controller{

 public static function adexHandler(array $data){
    if (empty($data['method']) || !str_starts_with($data['method'], 'adex')) {
        return null;
    }
    // Extract the Adex ID from the method name    
     $adexID = str_replace('adex', '', $data['method']);
    if (empty($adexID)) {
        return null;
    }
    $AdexAPI = AdexAPI::find($adexID);
    if (empty($AdexAPI) || is_null($AdexAPI->website_url) || is_null($AdexAPI->username) || is_null($AdexAPI->password)) {
        return null;
    }
    $accessToken = base64_encode($AdexAPI->username . ":" . $AdexAPI->password);
    $response = Http::withHeaders(["Authorization" => "Basic " . $accessToken])
        ->post($AdexAPI->website_url . '/api/user');
    if (!$response->successful() || empty($response->json('AccessToken'))) {
        return null;
    }
    $access_token = $response->json('AccessToken');
    $billPlan = BillPlan::where('plan_id', $data['disco'])->first();
    $adexPlanId = null;
    foreach ($billPlan->api_mappings as $map) {
        if ($map['adex_api_id'] == $AdexAPI->id) {
            $adexPlanId = $map['plan_id'];
            break;
        }
    }
    if (empty($adexPlanId)) {
        return null;
    }
    $payload = array(
        'meter_type' => $data['meter_type'],
        'meter_number' => $data['meter_number'],
        'disco' => $adexPlanId
    );
    $PResponse = Http::withHeaders([
        "Authorization" => "Token $access_token",
        "Content-Type" => "application/json"
    ])->get($AdexAPI->website_url . '/api/bill/bill-validation', $payload);
    if ($PResponse->successful() && !empty($PResponse->json('name'))) {
        return $PResponse->json('name');
    } 
    return null;
}

 public static function quickly(array $data){
     $quicklySim = OtherApi::first();
    if (is_null($quicklySim) || is_null($quicklySim->quickly_email) || is_null($quicklySim->quickly_pass)) {
            return null;
    }
    $accessToken = base64_encode($quicklySim->quickly_email . ":" . $quicklySim->quickly_pass);
    $response = Http::withHeaders(["Authorization" => "Basic " . $accessToken])
        ->post('https://quicklysim.com/api/user');
    if (!$response->successful() || empty($response->json('AccessToken'))) {
        return null;
    }
    $access_token = $response->json('AccessToken');
    $billPlan = BillPlan::where('plan_id', $data['disco'])->first();
    $payload = array(
        'meter_type' => $data['meter_type'],
        'meter_number' => $data['meter_number'],
        'disco' => $billPlan->quickly
    );
    $PResponse = Http::withHeaders([
        "Authorization" => "Token $access_token",
        "Content-Type" => "application/json"
    ])->get('https://quicklysim.com/api/bill/bill-validation', $payload);
    if ($PResponse->successful() && !empty($PResponse->json('name'))) {
        return $PResponse->json('name');
    } 
    return null;
}
public static function Vtpass($data)
{
    $other_api = OtherApi::first();
    $bill = BillPlan::where('plan_id', $data['disco'])->first();

    if (!$other_api || !$bill) {
        return null; 
    }

    $vtpass_token = base64_encode($other_api->vtpass_username . ":" . $other_api->vtpass_password);

    $postdata = [
        'serviceID'   => $bill->vtpass,
        'billersCode' => $data['meter_number'],
        'type'        => $data['meter_type'],
    ];

    // Send request using Laravel HTTP client
    $response = Http::withHeaders([
        'Authorization' => 'Basic ' . $vtpass_token,
        'Content-Type'  => 'application/json',
    ])->post('https://vtpass.com/api/merchant-verify', $postdata);

    if ($response->successful()) {
        $json = $response->json();
        return $json['content']['Customer_Name'] ?? null;
    }
    return null;
}

}
