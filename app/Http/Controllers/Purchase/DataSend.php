<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\AdexAPI;
use App\Models\Network;
use App\Models\DataPlan;
use App\Models\MsorgAPI;
use App\Models\VirusAPI;
use App\Models\OtherApi;
use App\Models\User;
use App\Models\General;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\PurchaseNotification;

class  DataSend extends Controller
{
    /**
     * Handle Adex API requests.
     *
     * @param Data $data
     * @param DataPlan $dataplan
     * @return string
     */
 public static function adexHandler(Data $data, DataPlan $dataplan)
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

        $adexPlanId = null;
        foreach ($dataplan->api_mappings as $map) {
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
        $data->vending_url = $AdexAPI->website_url . "/api/data/";
        $data->save();

        $paypload = [
            'network' => $network->adex_id,
            'phone' => $data->plan_phone,
            'data_plan' => $adexPlanId,
            'bypass' => true,
            'request-id' => $data->my_trans_id
        ];

        $PResponse = Http::withHeaders([
            "Authorization" => "Token $access_token",
            "Content-Type" => "application/json"
        ])->timeout(60)->post($data->vending_url, $paypload);

        $data->response_time = now();
        $data->ServerResponse = $PResponse->body();
        $data->api_response = $PResponse->json('response') ?? null;
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

public function quickly(Data $data, DataPlan $dataplan){
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
        $response = Http::withHeaders(["Authorization" => "Basic " . $accessToken])->timeout(60)->post('https://quicklysim.com/api/user');
        if (!$response->successful() || empty($response->json('AccessToken'))) {
            $data->ServerResponse = $response->body() ?: 'Failed to retrieve access token';
            $data->save();
            return 'fail';
        }
        $access_token = $response->json('AccessToken');
        $data->request_time = now();
        $data->vending_url = "https://quicklysim.com/api/data";
        $data->save();
        $network = Network::where('network', $data->network)->first();
        $paypload = [
            'network' => $network->adex_id,
            'phone' => $data->plan_phone,
            'data_plan' => $dataplan->quickly,
            'bypass' => true,
            'request-id' => $data->my_trans_id
        ];

        $PResponse = Http::withHeaders([
            "Authorization" => "Token $access_token",
            "Content-Type" => "application/json"
        ])->timeout(60)->post($data->vending_url, $paypload);

        $data->response_time = now();
        $data->ServerResponse = $PResponse->body();
        $data->api_response = $PResponse->json('response') ?? null;
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

    /**
     * Handle MSORG API requests.
     *
     * @param Data $data
     * @param DataPlan $dataplan
     * @return string
     */
public static function msorgHandler(Data $data, DataPlan $dataplan)
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
    $data->vending_url = $MsorgAPI->{strtolower($data->vending_system) . '_website'} . "/api/data/";
    $data->save();
    $paypload = [
        'network' => $network->msorg_id,
        'mobile_number' => $data->plan_phone,
        'plan' => $dataplan->{strtolower($data->vending_system)},
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
        $data->api_response = $response->json('api_response') ?? null;
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

    /**
     * Handle Virus API requests.
     *
     * @param Data $data
     * @param DataPlan $dataplan
     * @return string
     */
    public static function virusHandler(Data $data, DataPlan $dataplan){
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
        $data->vending_url = $virusAPI->{strtolower($data->vending_system) . '_website'} . "/api/data/";
        $data->save();
          if ($data->network == 'MTN' and $data->network_type == 'GIFTING') {
                $virus_id = "gifting";
            } else if ($data->network == 'AIRTEL' and $data->network_type == 'COOPERATE GIFTING') {
                $virus_id = "airtel-cg";
            } else {
                $virus_id = $network->virus_id;
            }
        $paypload = [
            'network' => $virus_id,
            'mobile' => $data->plan_phone,
            'plan_code' => $dataplan->{strtolower($data->vending_system)},
            'token' => $virusAPI->{strtolower($data->vending_system)},
            'request_id' => $data->my_trans_id
        ];
        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])->timeout(60)->post($data->vending_url, $paypload);
            $data->response_time = now();
            $data->ServerResponse = $response->body();
            $data->api_response = $response->json('desc') ?? null;
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
    /**
     * Handle Simhosting API requests.
     *
     * @param Data $data
     * @param DataPlan $dataplan
     * @return string
     */
    public static function simhosting(Data $data, DataPlan $dataplan){
        if ($data->plan_status != 0 || is_null($data->vending_system)) {
            return 'process';
        }

        $simhostingAPI = OtherApi::first();
        if (is_null($simhostingAPI) || is_null($simhostingAPI->simhosting)) {
            $data->ServerResponse = 'Missing Simhosting API credentials';
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
        $data->vending_url = 'https://api.mysimhosting.com/v1/data';
        $data->save();
        $paypload = [
            'network' => 1,
            'number' => $data->plan_phone,
            'plan' => $dataplan->simhosting,
            'senderID' => $data->my_trans_id
        ];
        $headers = [
            "Authorization" =>  "Bearer " . $simhostingAPI->simhosting,
            "Content-Type" => "application/json"
        ];
        try {
            $response = Http::withHeaders($headers)->timeout(60)->post($data->vending_url, $paypload);
            $data->response_time = now();
            $data->ServerResponse = $response->body();
            $data->api_response = $response->json('data.msg') ?? null;
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
     /**
     * Handle smeplug API requests.
     *
     * @param Data $data
     * @param DataPlan $dataplan
     * @return string
     */
    public static function smeplug(Data $data, DataPlan $dataplan){
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
        $data->vending_url = 'https://smeplug.ng/api/v1/data/purchase';
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
            'plan_id' => $dataplan->smeplug,
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
    public static function msplug(Data $data, DataPlan $dataplan)
    {
        return null;
    }
    /**
     * Handle Simserver API requests.
     *
     * @param Data $data
     * @param DataPlan $dataplan
     * @return string
     */
    public static function simserver(Data $data, DataPlan $dataplan){
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
            'product_code' => $dataplan->simserver,
            'callback' => null,
            'user_reference' =>$data->my_trans_id
        ];
        try {
            $response = Http::timeout(60)->post($data->vending_url, $paypload);
            $data->response_time = now();
            $data->ServerResponse = $response->body();
            $data->api_response = $response->json('data.true_response') ?? null;
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
    /**
     * Handle OG DAMNS API requests.
     *
     * @param Data $data
     * @param DataPlan $dataplan
     * @return string
     */
   public static function ogdamns(Data $data, DataPlan $dataplan){
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
        $data->vending_url = 'https://simhosting.ogdams.ng/api/v1/vend/data';
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
            'planId' => $dataplan->ogdamns,
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
    /**
     * Handle Gmail API requests.
     *
     * @param Data $data
     * @param DataPlan $dataplan
     * @return string
     */
public static function gmail(Data $data, DataPlan $dataplan)
{
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
    $adminUrl = url('/secure/ade-2025/internal/panel/data-transactions/' . $data->id . '/edit');
    $message = strtoupper($data->username) . ' wants to buy ' . $network->network . ' ' . $data->network_type . ' ' . $data->plan_name . ' ₦' . number_format($data->amount, 2) . ' to ' . $data->plan_phone . '. Reference is ' . $data->my_trans_id;
    $adminEmails = User::where(['status' => 1, 'type' => 'ADMIN'])->pluck('email')->toArray();
    $sets = General::first();
    $email_data = [
        'username' => $data->username,
        'title' => 'New Data Purchase Request',
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
  /**
     * Handle Easy access API requests.
     *
     * @param Data $data
     * @param DataPlan $dataplan
     * @return string
     */
public static function easyaccess(Data $data, DataPlan $dataplan){
    if ($data->plan_status != 0 || is_null($data->vending_system)) {
        return 'process';
    }
    $easyaccessAPI = OtherApi::first();
        if (is_null($easyaccessAPI) || is_null($easyaccessAPI->easyaccess)) {
            $data->ServerResponse = 'Missing Easy Access API credentials';
            $data->save();
            return 'fail';
        }
    
        $the_network = match ($network->network) {
            'MTN' => 01,
            'AIRTEL' => 03,
            'GLO' => 02,
            default => 04,
        };
    $data->request_time = now();
    $data->vending_url = 'https://easyaccessapi.com.ng/api/data.php';
    $data->save();
        $response = Http::withHeaders(['AuthorizationToken' => $other_api->easy_access,'cache-control' => 'no-cache',])->asForm()->post('https://easyaccessapi.com.ng/api/data.php', [
            'network' => $the_network,
            'mobileno' => $data->plan_phone,
            'dataplan' => $data->easyaccess,
            'client_reference' => $data->my_trans_id]);
       $data->response_time = now();
        $data->ServerResponse = $response->body();
        //$data->api_response = $response->json('data.msg') ?? null;
        $data->save();
          if ($response->successful()) {
            if ($response->json('success') === 'true') {
                return 'success';
            } elseif ($response->json('success') === 'false') {
                return 'fail';
            } else {
                return 'process';
            }
        } else {
            return 'fail';
        }
}
  /**
     * Handle megaplubcloud API requests.
     *
     * @param Data $data
     * @param DataPlan $dataplan
     * @return string
     */
public static function megasubcloud(Data $data, DataPlan $dataplan){
     if ($data->plan_status != 0 || is_null($data->vending_system)) {
        return 'process';
    }
       $megasubcloudAPI = OtherApi::first();
        if (is_null($megasubcloudAPI) || is_null($megasubcloudAPI->megasubcloud)) {
            $data->ServerResponse = 'Missing megasubcloud API credentials';
            $data->save();
            return 'fail';
        }
           $payload = [
                'network_api_id' => '5',
                'mobile_number' => $data->plan_phone,
                'data_api_id' => $dataplan->megasubcloud,
                'validatephonenetwork' => false,
                'duplication_check' => false
            ];
            $endpoints = 'https://www.101terabyte.com/API/?action=buy_data';
            $headers = [
                "Authorization" => "Token {$megasubcloudAPI->megasubcloud}",
                // "Content-Type" => "multipart/form-data",
                "Password" => ""
            ];
          $data->request_time = now();
          $data->vending_url = $endpoints;
          $data->save();
          $response = Http::withHeaders($headers)->asForm()->post($endpoints,$payload);
           $data->response_time = now();
        $data->ServerResponse = $response->body();
        $data->api_response = $response->json('Detail.info.realresponse') ?? null;
        $data->save();
        if($response->json('Detail.success') == 'true' && $response->json('Detail.info.Success') == 1){
            return 'success';
        }
        return 'fail';
}
  /**
     * Handle megasub API requests.
     *
     * @param Data $data
     * @param DataPlan $dataplan
     * @return string
     */
public static function megasub(Data $data, DataPlan $dataplan){
     if ($data->plan_status != 0 || is_null($data->vending_system)) {
        return 'process';
    }
       $megasubcloudAPI = OtherApi::first();
        if (is_null($megasubcloudAPI) || is_null($megasubcloudAPI->megasub)) {
            $data->ServerResponse = 'Missing megasub API credentials';
            $data->save();
            return 'fail';
        }
           $payload = [
                'network_api_id' => '5',
                'mobile_number' => $data->plan_phone,
                'data_api_id' => $dataplan->megasub,
                'validatephonenetwork' => false,
                'duplication_check' => false
            ];
            $endpoints = 'https://megasubplug.com/API/?action=buy_data';
            $headers = [
                "Authorization" => "Token {$megasubcloudAPI->megasub}",
                // "Content-Type" => "multipart/form-data",
                "Password" => ""
            ];
          $data->request_time = now();
          $data->vending_url = $endpoints;
          $data->save();
          $response = Http::withHeaders($headers)->asForm()->post($endpoints,$payload);
           $data->response_time = now();
        $data->ServerResponse = $response->body();
        $data->api_response = $response->json('Detail.info.realresponse') ?? null;
        $data->save();
        if($response->json('Detail.success') == 'true' && $response->json('Detail.info.Success') == 1){
            return 'success';
        }
        return 'fail';
}
}
