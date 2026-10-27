<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Airtime;
use App\Models\AdexAPI;
use App\Models\Network;
use App\Models\MsorgAPI;
use App\Models\VirusAPI;
use App\Models\OtherApi;
use App\Models\User;
use App\Models\General;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\PurchaseNotification;

class  AirtimeSend extends Controller
{

public static function adexHandler(Airtime $data)
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
    try {
        $accessToken = base64_encode($AdexAPI->username . ":" . $AdexAPI->password);
        $response = Http::withHeaders(["Authorization" => "Basic " . $accessToken])
            ->timeout(60)
            ->post($AdexAPI->website_url . '/api/user');
        if (!$response->successful() || empty($response->json('AccessToken'))) {
            $data->ServerResponse = $response->body() ?: 'Failed to retrieve access token';
            $data->save();
            return 'fail';
        }
        $access_token = $response->json('AccessToken');
        $network = Network::where('network', $data->network)->first();
        $data->request_time = now();
        $data->vending_url = $AdexAPI->website_url . "/api/topup/";
        $data->save();
        $paypload = array(
            'network' => $network->adex_id,
            'phone' => $data->plan_phone,
            'plan_type' => strtoupper($data->network_type),
            'bypass' => true,
            'amount' => $data->amount,
            'request-id' =>$data->my_trans_id
        );
        $PResponse = Http::withHeaders([
            "Authorization" => "Token $access_token",
            "Content-Type" => "application/json"
        ])->timeout(60)->post($data->vending_url, $paypload);

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
    } catch (\Exception $e) {
        $data->ServerResponse = 'Connection error: ' . $e->getMessage();
        $data->response_time = now();
        $data->save();
        return 'process';
    }
}
public function quickly(Airtime $data){
     if ($data->plan_status != 0 || is_null($data->vending_system)) {
            return 'process';
        }
        $quicklySim = OtherApi::first();
        if (is_null($quicklySim) || is_null($quicklySim->quickly_email) || is_null($quicklySim->quickly_pass)) {
            $data->ServerResponse = 'Missing Quicklsim API credentials';
            $data->save();
            return 'fail';
        }
    try {
        $accessToken = base64_encode($quicklySim->quickly_email . ":" . $quicklySim->quickly_pass);
        $response = Http::withHeaders(["Authorization" => "Basic " . $accessToken])
            ->timeout(60)
            ->post('https://quicklysim.com/api/user');
        if (!$response->successful() || empty($response->json('AccessToken'))) {
            $data->ServerResponse = $response->body() ?: 'Failed to retrieve access token';
            $data->save();
            return 'fail';
        }
        $access_token = $response->json('AccessToken');
        $network = Network::where('network', $data->network)->first();
        $data->request_time = now();
        $data->vending_url = "https://quicklysim.com/api/topup/";
        $data->save();
        $paypload = array(
            'network' => $network->adex_id,
            'phone' => $data->plan_phone,
            'plan_type' => strtoupper($data->network_type),
            'bypass' => true,
            'amount' => $data->amount,
            'request-id' =>$data->my_trans_id
        );
        $PResponse = Http::withHeaders([
            "Authorization" => "Token $access_token",
            "Content-Type" => "application/json"
        ])->timeout(60)->post($data->vending_url, $paypload);
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
    } catch (\Exception $e) {
        $data->ServerResponse = 'Connection error: ' . $e->getMessage();
        $data->response_time = now();
        $data->save();
        return 'process';
    }
}
public static function msorgHandler(Airtime $data)
{
     if ($data->plan_status != 0 || is_null($data->vending_system)) {
        return 'process';
    }
    $MsorgAPI = MsorgAPI::first();
    if (is_null($MsorgAPI) || is_null($MsorgAPI->{strtolower($data->vending_system)}) || is_null($MsorgAPI->{strtolower($data->vending_system) . '_website'})) {
        $data->ServerResponse = 'Missing Msorg API credentials';
        $data->save();
        return 'fail';
    }
    $network = Network::where('network', $data->network)->first();
    if (is_null($network)) {
        $data->ServerResponse = 'Network not found';
        $data->save();
        return 'fail';
    }
    $data->request_time = now();
    $data->vending_url = $MsorgAPI->{strtolower($data->vending_system) . '_website'} . "/api/topup/";
    $data->save();
    $paypload = [
        'network' => $network->msorg_id,
        'mobile_number' => $data->plan_phone,
        'airtime_type' => strtoupper($data->network_type),
        'amount' => $data->amount,
        'Ported_number' => true,
    ];
    $admin_details = [
        'Content-Type' => 'application/json',
        'Authorization' =>  'Token '. $MsorgAPI->{strtolower($data->vending_system)}
    ];
    try {
        $response = Http::withHeaders($admin_details)->timeout(60)->post($data->vending_url, $paypload);
        $data->response_time = now();
        $data->ServerResponse = $response->body();
        $data->save();
        if ($response->successful()) {
            if (in_array($response->json('Status'), ['processing', 'successful', 'pending', 'proccessing'])) {
                return 'success';
            } elseif ($response->json('Status') === 'failed') {
                return 'fail';
            } else {
                return 'process';
            }
        } else {
            return 'fail';
        }
    } catch (\Exception $e) {
        $data->ServerResponse = 'Connection timeout or error: ' . $e->getMessage();
        $data->response_time = now();
        $data->save();
        return 'process';
    }
}
public static function virusHandler(Airtime $data){
        if ($data->plan_status != 0 || is_null($data->vending_system)) {
            return 'process';
        }

        $virusAPI = VirusAPI::first();
        if (is_null($virusAPI) || is_null($virusAPI->{strtolower($data->vending_system)}) || is_null($virusAPI->{strtolower($data->vending_system) . '_website'})) {
            $data->ServerResponse = 'Missing Virus API credentials';
            $data->save();
            return 'fail';
        }

        $network = Network::where('network', $data->network)->first();
        if (is_null($network)) {
            $data->ServerResponse = 'Network not found';
            $data->save();
            return 'fail';
        }

        $data->request_time = now();
        $data->vending_url = $virusAPI->{strtolower($data->vending_system) . '_website'} . "/api/airtime";
        $data->save();
        $paypload = [
            'network' => $network->virus_id,
            'mobile' => $data->plan_phone,
            'amount' => $data->amount,
            'token' => $virusAPI->{strtolower($data->vending_system)},
            'request_id' => $data->my_trans_id
        ];
        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])->timeout(60)->post($data->vending_url, $paypload);
            $data->response_time = now();
            $data->ServerResponse = $response->body();
            $data->save();
            if ($response->successful()) {
                if ($response->json('status') === 'success') {
                    return 'success';
                } elseif ($response->json('status') === 'fail') {
                    return 'fail';
                } else {
                    return 'process';
                }
            } else {
                return 'fail';
            }
        } catch (\Exception $e) {
            $data->ServerResponse = 'Connection error: ' . $e->getMessage();
            $data->response_time = now();
            $data->save();
            return 'process';
        }
}

public static function smeplug(Airtime $data){
        if ($data->plan_status != 0 || is_null($data->vending_system)) {
            return 'process';
        }

        $smeplugAPI = OtherApi::first();
        if (is_null($smeplugAPI) || is_null($smeplugAPI->smeplug)) {
            $data->ServerResponse = 'Missing Smeplug API credentials';
            $data->save();
            return 'fail';
        }

        $network = Network::where('network', $data->network)->first();
        if (is_null($network)) {
            $data->ServerResponse = 'Network not found';
            $data->save();
            return 'fail';
        }

        $data->request_time = now();
        $data->vending_url = 'https://smeplug.ng/api/v1/airtime/purchase';
        $data->save();

        $the_network = match ($network->network) {
            'MTN' => '1',
            'AIRTEL' => '2',
            'GLO' => '4',
            default => '3',
        };

        $paypload = [
            'network_id' => $the_network,
            'phone' => $data->plan_phone,
            'amount' => $data->amount,
            'customer_reference' => $data->my_trans_id
        ];
        
        $headers = [
            "Authorization" => "Bearer " . $smeplugAPI->smeplug,
            "Content-Type" => "application/json"
        ];

        try {
            $response = Http::withHeaders($headers)->timeout(60)->post($data->vending_url, $paypload);
            $data->response_time = now();
            $data->ServerResponse = $response->body();
            $data->api_response = $response->json('data.msg') ?? null;
            $data->save();
            if ($response->successful()) {
                if ($response->json('status') === true) {
                    return 'success';
                } elseif ($response->json('status') === false) {
                    return 'fail';
                } else {
                    return 'process';
                }
            } else {
                return 'fail';
            }
        } catch (\Exception $e) {
            $data->ServerResponse = 'Connection error: ' . $e->getMessage();
            $data->response_time = now();
            $data->save();
            return 'process';
        }
    }
    public static function Msplug(Airtime $data)
    {
        return null;
    }
     public static function simserver(Airtime $data){
        if ($data->plan_status != 0 || is_null($data->vending_system)) {
            return 'process';
        }

        $simserverAPI = OtherApi::first();
        if (is_null($simserverAPI) || is_null($simserverAPI->simserver)) {
            $data->ServerResponse = 'Missing Simserver API credentials';
            $data->save();
            return 'fail';
        }

        $network = Network::where('network', $data->network)->first();
        if (is_null($network)) {
            $data->ServerResponse = 'Network not found';
            $data->save();
            return 'fail';
        }

        $data->request_time = now();
        $data->vending_url = 'https://api.simservers.io';
        $data->save();

        $paypload = [
            'process' => "buy",
            'recipient' => $data->plan_phone,
            'api_key' => $simserverAPI->simserver,
            'amount' => $data->amount,
            'callback' => null,
            'user_reference' =>$data->my_trans_id
        ];
        try {
            $response = Http::timeout(60)->post($data->vending_url, $paypload);
            $data->response_time = now();
            $data->ServerResponse = $response->body();
            $data->save();
            if ($response->successful()) {
                if ($response->json('status') === 'success') {
                    return 'success';
                } elseif ($response->json('status') === 'fail') {
                    return 'fail';
                } else {
                    return 'process';
                }
            } else {
                return 'fail';
            }
        } catch (\Exception $e) {
            $data->ServerResponse = 'Connection error: ' . $e->getMessage();
            $data->response_time = now();
            $data->save();
            return 'process';
        }
    }
    public static function ogdamns(Airtime $data){
        if ($data->plan_status != 0 || is_null($data->vending_system)) {
            return 'process';
        }

        $ogdamnsAPI = OtherApi::first();
        if (is_null($ogdamnsAPI) || is_null($ogdamnsAPI->ogdamns)) {
            $data->ServerResponse = 'Missing Ogdamns API credentials';
            $data->save();
            return 'fail';
        }

        $network = Network::where('network', $data->network)->first();
        if (is_null($network)) {
            $data->ServerResponse = 'Network not found';
            $data->save();
            return 'fail';
        }

        $data->request_time = now();
        $data->vending_url = 'https://simhosting.ogdams.ng/api/v1/vend/airtime';
        $data->save();

        $the_network = match ($network->network) {
            'MTN' => '1',
            'AIRTEL' => '2',
            'GLO' => '3',
            default => '4',
        };
        $paypload = [
            'networkId' => $the_network,
            'phoneNumber' => $data->plan_phone,
            'amount' => $data->amount,
            'type' => strtolower($data->network_type),
            'reference' => $data->my_trans_id
        ];
        $headers = [
            "Authorization" => "Bearer " . $ogdamnsAPI->ogdamns,
            'Content-Type' => 'application/json'
        ];
        try {
            $response = Http::withHeaders($headers)->timeout(60)->post($data->vending_url, $paypload);
            $data->response_time = now();
            $data->ServerResponse = $response->body();
            $data->save();
            if ($response->successful()) {
                if ($response->json('status') === true) {
                    return 'success';
                } elseif ($response->json('status') === false) {
                    return 'fail';
                } else {
                    return 'process';
                }
            } else {
                return 'fail';
            }
        } catch (\Exception $e) {
            $data->ServerResponse = 'Connection error: ' . $e->getMessage();
            $data->response_time = now();
            $data->save();
            return 'process';
        }
    }
   public static function vtpass(Airtime $data){
    // Skip if already processed or no system set
    if ($data->plan_status != 0 || is_null($data->vending_system)) {
        return 'process';
    }

    $network = Network::where('network', $data->network)->first();
    $api = OtherApi::first();
    if (is_null($network)) {
        $data->ServerResponse = 'Network not found';
        $data->save();
        return 'fail';
    }
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
        'serviceID' => strtolower($network->network == '9mobile'? 'etisalat' : $network->network),
        'phone' => $data->plan_phone,
        'amount' => $data->amount,
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
public static function gmail(Airtime $data){
    if ($data->plan_status != 0 || is_null($data->vending_system)) {
        return 'process';
    }

    $network = Network::where('network', $data->network)->first();
    if (is_null($network)) {
        $data->ServerResponse = 'Network not found';
        $data->save();
        return 'fail';
    }

    $data->request_time = now();
    $data->vending_url = 'gmail.com';
    $data->save();
    $adminUrl = url('/secure/ade-2025/internal/panel/airtime-transactions/' . $data->id . '/edit');
    $message = strtoupper($data->username) . ' wants to buy ' . $network->network . ' ' . $data->network_type . ' ₦' . number_format($data->amount, 2) . ' to ' . $data->plan_phone . '.  Reference is ' . $data->my_trans_id;
    $adminEmails = User::where(['status' => 1, 'type' => 'ADMIN'])->pluck('email')->toArray();
    $sets = General::first();
    $email_data = [
        'username' => $data->username,
        'title' => 'New Airtime Purchase Request',
        'sender_mail' => $sets->app_email,
        'app_name' => $sets->app_name,
        'mes' => $message,
        'action_url' => $adminUrl, 
        'action_text' => 'View Transaction',
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
