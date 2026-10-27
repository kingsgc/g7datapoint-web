<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\AdexAPI;
use App\Models\DataCard;
use App\Models\DataCardPlan;
use App\Models\StoreDataCard;
use App\Models\DumpDataCardPin;
use App\Models\Network;

class DataCardSend extends Controller
{
    public static function adexHandler(DataCard $data, DataCardPlan $plan)
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
        $data->vending_url = $AdexAPI->website_url . "/api/data_card";
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
                    if (DumpDataCardPin::where([
                        'network' => $network->network,
                        'serial'  => $load_serial,
                        'pin'     => $load_pin,
                        'transid' => $data->my_trans_id
                    ])->count() == 0) {
                        if ((!empty($load_pin)) && (!empty($load_serial))) {
                            DumpDataCardPin::create([
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

    public static function self(DataCard $data, DataCardPlan $plan)
    {
        return DB::transaction(function () use ($data, $plan) {
            // Mark vending start
            $data->request_time = now();
            $data->vending_url = 'self-vend';
            $data->save();

            // Check stock
            $availableCards = StoreDataCard::where([
                    'network'      => $data->network,
                    'plan_status'  => 0,
                    'data_card_id' => $plan->id
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
                    $exists = DumpDataCardPin::where([
                        'network' => $data->network,
                        'serial'  => $serial,
                        'pin'     => $pin,
                        'transid' => $data->my_trans_id
                    ])->exists();

                    if (!$exists) {
                        DumpDataCardPin::create([
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
