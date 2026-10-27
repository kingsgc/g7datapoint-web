<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\AdexAPI;
use App\Models\RechargeCard;
use App\Models\RechargeCardPlan;
use App\Models\StoreRechargeCard;
use App\Models\DumpRechargeCardPin;
use App\Models\Network;

class RechargeCardSend extends Controller
{
    public static function adexHandler(RechargeCard $data, RechargeCardPlan $plan)
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

        $network = Network::where('network', $data->network)->first();
        $adexPlanId = null;
        foreach ($plan->api_mappings as $map) {
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

        $access_token = $response->json('AccessToken');
        $data->request_time = now();
        $data->vending_url = $AdexAPI->website_url . "/api/recharge_card";
        $data->save();

        $payload = [
            'network'    => $network->adex_id,
            'quantity'   => $data->quantity,
            'request-id' => $data->my_trans_id,
            'plan_type'  => $adexPlanId,
            'card_name'  => $data->card_name,
        ];

        $PResponse = Http::withHeaders([
            "Authorization" => "Token $access_token",
            "Content-Type"  => "application/json"
        ])->post($data->vending_url, $payload);

        $data->response_time = now();
        $data->ServerResponse = $PResponse->body();
        $data->save();

        $pin = explode(',', $PResponse->json('pin'));
        $serial = explode(',', $PResponse->json('serial'));

        for ($i = 0; $i < count($pin); $i++) {
            $load_pin = $pin[$i];
            $j = $i;
            for ($a = 0; $a < count($serial); $a++) {
                $load_serial = $serial[$a];
                if ($j == $a) {
                    if (DumpRechargeCardPin::where([
                        'network' => $network->network,
                        'serial'  => $load_serial,
                        'pin'     => $load_pin,
                        'transid' => $data->my_trans_id
                    ])->count() == 0) {
                        if ((!empty($load_pin)) && (!empty($load_serial))) {
                            DumpRechargeCardPin::create([
                                'username' => $data->username,
                                'user_id' => $data->user_id,
                                'serial'   => $load_serial,
                                'pin'      => $load_pin,
                                'network'  => $data->network,
                                'date'     => $data->plan_date,
                                'my_trans_id'  => $data->my_trans_id,
                                'transid'  => $data->transid,
                            ]);
                        }
                    }
                }
            }
        }

        return match ($PResponse->json('status')) {
            'success' => 'success',
            'fail'    => 'fail',
            'process' => 'process',
            default   => 'fail',
        };
    }
    public static function mobilesub(RechargeCard $data, RechargeCardPlan $plan){
            $data->request_time = now();
            $data->vending_url = 'https://mobilesub.ng/api/rechargepin/';
            $data->save();
            $network = Network::where('network', $data->network)->first();
            if (!$network) {
                $data->response_time = now();
                $data->ServerResponse = 'Network configuration not found for ' . $data->network;
                $data->save();
                return 'fail';
            }
            $response = Http::withHeaders([
                'Authorization' => 'Token 560626160fabc811731597d13ad0eea9c64989fd',
                'content-type' => 'application/json'
                ])->post('https://mobilesub.ng/api/rechargepin/', [
                "network" => $network->msorg_id,
                "network_amount" => $plan->free2,
                "quantity" =>  $data->quantity,
                "name_on_card" => $data->card_name
              ]);
              $data->response_time = now();
              $data->ServerResponse = $response->body();
              $data->save();
              if(!$response->successful() || strtolower($response->json('Status')) != 'successful' ){
                  return 'fail';
              }
              $dataPins = $response->json('data_pin');
              if (!is_array($dataPins)) {
                  return 'fail';
              }
              foreach($dataPins as $adex){
                  if (!is_array($adex) || empty($adex['pin']) || empty($adex['sno'])) {
                      continue;
                  }
                  $pin = $adex['pin'];
                  $serial = $adex['sno'];
                  if (DumpRechargeCardPin::where([
                        'network' => $network->network,
                        'serial'  => $serial,
                        'pin'     => $pin,
                        'transid' => $data->my_trans_id
                    ])->count() == 0) {
                        DumpRechargeCardPin::create([
                            'username' => $data->username,
                            'user_id' => $data->user_id,
                            'serial'   => $serial,
                            'pin'      => $pin,
                            'network'  => $data->network,
                            'date'     => $data->plan_date,
                            'my_trans_id'  => $data->my_trans_id,
                            'transid'  => $data->transid,
                         ]);
                }
              }
              return 'success';
    }
    public static function self(RechargeCard $data, RechargeCardPlan $plan)
    {
        return DB::transaction(function () use ($data, $plan) {
            // Mark vending start
            $data->request_time = now();
            $data->vending_url = 'self-vend';
            $data->save();

            // Check stock
            $availableCards = StoreRechargeCard::where([
                    'network'      => $data->network,
                    'plan_status'  => 0,
                    'recharge_card_id' => $plan->id
                ])
                ->take($data->quantity)
                ->get();

            if ($availableCards->count() < $data->quantity) {
                $data->response_time = now();
                $data->ServerResponse = 'Not enough stock available for self vending.';
                $data->save();
                return 'fail';
            }

            $pins = [];
            $serials = [];

            foreach ($availableCards as $card) {
                $pins[] = $card->pin;
                $serials[] = $card->serial;

                $card->update([
                    'plan_status'     => 1,
                    'buyer_username'  => $data->username,
                    'bought_date'     => $data->plan_date
                ]);
            }

            // Save to dump table
            foreach ($pins as $i => $pin) {
                $serial = $serials[$i] ?? null;

                if ($pin && $serial) {
                    $exists = DumpRechargeCardPin::where([
                        'network' => $data->network,
                        'serial'  => $serial,
                        'pin'     => $pin,
                        'transid' => $data->my_trans_id
                    ])->exists();

                    if (!$exists) {
                        DumpRechargeCardPin::create([
                            'username' => $data->username,
                            'user_id' => $data->user_id,
                            'serial'   => $serial,
                            'pin'      => $pin,
                            'network'  => $data->network,
                            'date'     => $data->plan_date,
                            'my_trans_id'  => $data->my_trans_id,
                            'transid'  => $data->transid,
                        ]);
                    }
                }
            }
            // Mark vending end
            $data->response_time = now();
            $data->ServerResponse = json_encode([
                'status'   => 'success',
                'message'  => 'Self vending completed successfully.',
                'pins'     => $pins,
                'serials'  => $serials
            ]);
            $data->save();
            return 'success';
        });
    }
    
    
}
