<?php
namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\AdexAPI;
use App\Models\OtherApi;
use App\Models\CableID;
use Illuminate\Support\Facades\Http;

class  IUCsend extends Controller {

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
  
    $payload = array(
        'iuc' => $data['iuc'],
        'cable' => $data['cable']
    );
    $PResponse = Http::withHeaders([
        "Authorization" => "Token $access_token",
        "Content-Type" => "application/json"
    ])->get($AdexAPI->website_url . '/api/cable/cable-validation', $payload);
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
    $payload = array(
        'iuc' => $data['iuc'],
        'cable' => $data['cable']
    );
    $PResponse = Http::withHeaders([
        "Authorization" => "Token $access_token",
        "Content-Type" => "application/json"
    ])->get('https://quicklysim.com/api/cable/cable-validation', $payload);
    if ($PResponse->successful() && !empty($PResponse->json('name'))) {
        return $PResponse->json('name');
    } 
    return null;
}
public static function vtpass($data){
    $other_api = OtherApi::first();
    $cable = CableID::where('plan_id', $data['cable'])->first();

    if (!$cable || !$other_api) {
        return null; 
    }
    $cable_name = strtolower($cable->cable_name) === 'startime' ? 'startimes' : strtolower($cable->cable_name);
    $vtpass_token = base64_encode($other_api->vtpass_username . ":" . $other_api->vtpass_password);
    $postData = [
        'serviceID'   => $cable_name,
        'billersCode' => $data['iuc'],
    ];
    $response = Http::withHeaders([
        'Authorization' => 'Basic ' . $vtpass_token,
        'Content-Type'  => 'application/json',
    ])->post('https://vtpass.com/api/merchant-verify', $postData);
    if ($response->successful()) {
        $body = $response->json();
        if (!empty($body['content']['Customer_Name'])) {
            return $body['content']['Customer_Name'];
        }
    }
    return null;
}
}
