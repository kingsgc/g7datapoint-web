<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Exam;
use App\Models\AdexAPI;
use App\Models\ExamId;
use App\Models\OtherApi;
use App\Models\StockResultPin;
use Illuminate\Support\Facades\Http;

class ExamSend extends Controller
{
    public static function adexHandler(Exam $data)
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
        $exam_id = ExamId::where('exam_name', strtolower($data->exam_name))->first();

        $data->request_time = now();
        $data->vending_url = $AdexAPI->website_url . "/api/exam";
        $data->save();

        $payload = [
            'exam'       => $exam_id->plan_id,
            'quantity'   => $data->quantity,
            'request-id' => $data->my_trans_id
        ];

        $PResponse = Http::withHeaders([
            "Authorization" => "Token $access_token",
            "Content-Type"  => "application/json"
        ])->post($data->vending_url, $payload);

        $data->response_time = now();
        $data->ServerResponse = $PResponse->body();
        $data->purchase_code = $PResponse->json('pin');
        $data->save();

        return match ($PResponse->json('status')) {
            'success' => 'success',
            'fail'    => 'fail',
            'process' => 'process',
            default   => 'fail',
        };
    }
public function quickly(Exam $data){
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
    $data->vending_url = "https://quicklysim.com/api/exam";
    $data->save();
    $payload = [
            'exam'       => $exam_id->plan_id,
            'quantity'   => $data->quantity,
            'request-id' => $data->my_trans_id
        ];
        $PResponse = Http::withHeaders([
            "Authorization" => "Token $access_token",
            "Content-Type"  => "application/json"
        ])->post($data->vending_url, $payload);
        $data->response_time = now();
        $data->ServerResponse = $PResponse->body();
        $data->purchase_code = $PResponse->json('pin');
        $data->save();
        return match ($PResponse->json('status')) {
            'success' => 'success',
            'fail'    => 'fail',
            'process' => 'process',
            default   => 'fail',
        };
}
    public static function easyaccess(Exam $exam)
    {
        $otherApi = OtherApi::first();
        if (!$otherApi || empty($otherApi->easy_access)) {
            $exam->ServerResponse = 'Missing Easy Access API credentials.';
            $exam->save();
            return 'fail';
        }
        $payload = ['no_of_pins' => $exam->quantity];
        $endpoints = match (strtoupper($exam->exam_name)) {
            'WAEC'   => "https://easyaccessapi.com.ng/api/waec_v2.php",
            'NECO'   => "https://easyaccessapi.com.ng/api/neco_v2.php",
            'NABTEB' => "https://easyaccessapi.com.ng/api/nabteb_v2.php",
            default  => null,
        };
        if (!$endpoints) {
            $exam->ServerResponse = 'Unsupported exam type for Easy Access API.';
            $exam->save();
            return 'fail';
        }
        $exam->request_time = now();
        $response = Http::withHeaders([
            "AuthorizationToken" => $otherApi->easy_access,
            "Cache-Control"       => "no-cache"
        ])->post($endpoints, $payload);

        $exam->response_time = now();
         $exam->ServerResponse = $response->body();
        $exam->save();
        if (!$response->successful() || !isset($response['success'])) {
            return 'fail';
        }
        if ($response['success'] !== 'true') {
            return 'fail';
        }
        $pins = [];
        for ($i = 1; $i <= $exam->quantity; $i++) {
            $key = $i === 1 ? 'pin' : "pin{$i}";
            if ($response->json($key)) {
                $pins[] = "pin{$i} => " . $response->json($key);
            }
        }
        $exam->purchase_code = implode(' ', $pins);
        $exam->save();
        return 'success';
    }

    public function vtpass(Exam $exam)
    {
        $otherApi = OtherApi::first();
        if (!$otherApi || !$otherApi->vtpass_username || !$otherApi->vtpass_password) {
            $exam->ServerResponse = 'Missing VTPass API credentials.';
            $exam->save();
            return 'fail';
        }

        $payload = [
            'serviceID'      => 'waec',
            'phone'          => $this->core()->app_phone,
            'variation_code' => 'waecdirect',
            'request_id'     => Carbon::now()->format('YmdHis') . '_' . $exam->my_trans_id
        ];

        $exam->request_time = now();
        $response = Http::withHeaders([
            "Authorization" => "Basic " . base64_encode($otherApi->vtpass_username . ":" . $otherApi->vtpass_password),
            'Content-Type'  => 'application/json'
        ])->post("https://vtpass.com/api/pay", $payload);
         $exam->response_time = now();
         $exam->ServerResponse = $response->body();
         $exam->purchase_code = $response->json('purchased_code');
         $exam->save();
        if (!$response->successful() || !isset($response['code'])) {
            return 'fail';
        }
        if ($response->json('code') == 000 && $response->json('purchased_code')) {
            return 'success';
        }
        if ($response->json('response_description') != 'TRANSACTION SUCCESSFUL') {
            return 'fail';
        }
        return 'process';
    }

    public static function self(Exam $exam)
    {
        $exam->request_time = now();
        $availablePins = StockResultPin::where('exam_name', $exam->exam_name)
            ->where('plan_status', 0)
            ->take($exam->quantity)
            ->get(); 
        $exam->response_time = now();
        if ($availablePins->count() < $exam->quantity) {
            $exam->ServerResponse = 'Not enough PINs in stock.';
            $exam->save();
            return 'fail';
        }
        $pins = [];
        foreach ($availablePins as $pin) {
            $pins[] = $pin->exam_pin . "<=>" . $pin->exam_serial;
            $pin->update([
                'plan_status'    => 1,
                'buyer_username' => $exam->username,
                'bought_date'    => $exam->plan_date
            ]);
        }
        $exam->purchase_code = implode(' ', $pins);
        $exam->ServerResponse = 'PIN(s) issued from internal stock.';
        $exam->save();
        return 'success';
    }
}
