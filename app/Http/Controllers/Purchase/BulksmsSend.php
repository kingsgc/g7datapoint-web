<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\BulkSms;
use Illuminate\Support\Facades\Http;
use App\Models\OtherApi;
use App\Models\AdexAPI;
class  BulksmsSend extends Controller
{
     public static function adexHandler(BulkSms $data)
    {
        if ($data->plan_status != 0 || is_null($data->vending_system)) {
            return 'fail';
        }

        $adexID = str_replace('adex', '', $data->vending_system);
        if (empty($adexID)) {
            return 'fail';
        }
        $AdexAPI = AdexAPI::find($adexID);
        if (!$AdexAPI || !$AdexAPI->website_url || !$AdexAPI->username || !$AdexAPI->password) {
            $data->ServerResponse = 'Missing Adex API credentials or URL.';
            $data->save();
            return 'fail';
        }
        $accessToken = base64_encode($AdexAPI->username . ":" . $AdexAPI->password);
        $response = Http::withHeaders(["Authorization" => "Basic " . $accessToken])
            ->post($AdexAPI->website_url . '/api/user');

        if (!$response->successful() || empty($response->json('AccessToken'))) {
            $data->ServerResponse = $response->body() ?: 'Failed to retrieve access token.';
            $data->save();
            return 'fail';
        }
        $access_token = $response->json('AccessToken');
        $data->request_time = now();
        $data->vending_url = $AdexAPI->website_url . "/api/bulksms";
        $data->save();
        $payload = [
            'sender' => $data->sender_name,
             'number' => $data->correct_number,
             'message' => $data->message,
            'request-id' => $data->my_trans_id
        ];
        $PResponse = Http::withHeaders([
            "Authorization" => "Token $access_token",
            "Content-Type"  => "application/json"
        ])->post($data->vending_url, $payload);
        $data->response_time = now();
        $data->ServerResponse = $PResponse->body();
        $data->save();
        return match ($PResponse->json('status')) {
            'success' => 'success',
            'fail'    => 'fail',
            'process' => 'process',
            default   => 'fail',
        };
    }
    public static function Hollatag(BulkSms $data){

    $adex_api = OtherApi::first();
    if (!$adex_api || !$adex_api->hollatag_username || !$adex_api->hollatag_password) {
          $data->ServerResponse = 'Missing hollatags API credentials.';
          $data->save();
        return 'fail';
    }
    $payload = [
        "user" => $adex_api->hollatag_username,
        "pass" => $adex_api->hollatag_password,
        "from" => $data->sender_name,
        "to"   => $data->correct_number,
        "msg"  => $data->message,
        "type" => 0,
    ];
    $data->request_time = now();
    $data->vending_url = 'https://sms.hollatags.com/api/send/';
    $data->save();
    $response = Http::asForm()->post('https://sms.hollatags.com/api/send/', $payload);
    $data->response_time = now();
    $data->ServerResponse = $response->body();
    $data->save();
    if (!$response->successful()) {
        return 'fail';
    }
    $body = trim($response->body());
    return match ($body) {
        'sent' => 'success',
        default => 'fail',
    };
}
}
