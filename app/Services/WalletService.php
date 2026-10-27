<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletFunding;
use App\Models\AdexKey;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
class WalletService
{
    /**
     * Insert a wallet funding record for a user if it doesn't already exist.
     */
    public function insertStock(User $user): void
    {
        if (!WalletFunding::where('user_id', $user->id)->exists()) {
            WalletFunding::create([
                'user_id'  => $user->id,
                'username' => $user->username,
            ]);
        }
    }

    /**
     * Generate a virtual account or initialize account-related services.
     * (Implement your logic here)
     */
    public function generateAccount(User $user): void {
        if(is_null($user->palmpay) || is_null($user->kolo_moni) || is_null($user->safe)){
            $adexKey = AdexKey::first();
            $bankCode = [];
            if(is_null($user->palmpay)){
                $bankCode[] = "20867";
            }
            if(is_null($user->kolo_moni)){
                $bankCode[] = '20987';
            }
            if(is_null($user->safe)){
                $bankCode[] = '29007';
            }
          $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $adexKey->x_skey,
                'api-key' => $adexKey->x_akey,
                'Content-Type' => 'application/json',
            ])->post('https://api.xixapay.com/api/v1/createVirtualAccount', [
                "name" =>  $user->username,
                "email" => $user->email,
                "bankCode" => $bankCode,
                "phoneNumber" => $user->phone,
                "businessId" => $adexKey->x_business_id,
                "accountType" => "static",
            ]);
            if($response->successful()){
                 $bankAccounts = $response->json('bankAccounts') ?? [];
                foreach($bankAccounts as $bank){
                        if($bank['bankCode'] == '20867'){
                            $user->palmpay = $bank['accountNumber'];
                        }
                        if($bank['bankCode'] == '20987'){
                            $user->kolo_moni = $bank['accountNumber'];
                        }
                        if($bank['bankCode'] == '29007'){
                            $user->safe = $bank['accountNumber'];
                        }
                }
                $user->save();
            }
        }

        $this->generateMonnifyAccount($user);
    }

    /**
     * Generate (or top-up) a user's Monnify reserved bank accounts (Wema/Rolex/Sterlen).
     */
    private function generateMonnifyAccount(User $user): void
    {
        $setting = Setting::first();
        if (!$setting || (int) $setting->monnify_atm !== 1) {
            return;
        }

        if (!is_null($user->wema) && !is_null($user->rolex) && !is_null($user->sterlen)) {
            return;
        }

        $adexKey = AdexKey::first();
        if (!$adexKey || empty($adexKey->mon_app_key) || empty($adexKey->mon_sk_key)) {
            return;
        }

        $accessToken = $this->getMonnifyAccessToken($adexKey);
        if (!$accessToken) {
            return;
        }

        $preferredBanks = [];
        if (is_null($user->wema)) {
            $preferredBanks[] = '035';
        }
        if (is_null($user->rolex)) {
            $preferredBanks[] = '50515';
        }
        if (is_null($user->sterlen)) {
            $preferredBanks[] = '232';
        }

        $headers = [
            'Authorization' => "Bearer $accessToken",
            'Content-Type' => 'application/json',
        ];

        if (is_null($user->monify_ref)) {
            $ref = $this->generateMonnifyReference();

            $response = Http::withHeaders($headers)->post('https://api.monnify.com/api/v2/bank-transfer/reserved-accounts', [
                'accountReference' => $ref,
                'accountName' => $user->username,
                'currencyCode' => 'NGN',
                'contractCode' => $adexKey->mon_con_num,
                'customerEmail' => $user->email,
                'bvn' => $adexKey->mon_bvn,
                'customerName' => $user->username,
                'getAllAvailableBanks' => false,
                'preferredBanks' => $preferredBanks,
            ]);
            $value = $response->json();

            if (!($value['requestSuccessful'] ?? false)) {
                Log::warning('Monnify reserved account creation failed', [
                    'user_id' => $user->id,
                    'response' => $value,
                ]);
                return;
            }

            $this->applyMonnifyAccounts($user, $value['responseBody']['accounts'] ?? []);
            $user->autofund = 'ACTIVE';
            $user->monify_ref = $ref;
            $user->save();
            return;
        }

        $url = 'https://api.monnify.com/api/v1/bank-transfer/reserved-accounts/add-linked-accounts/' . $user->monify_ref;

        $response = Http::withHeaders($headers)->put($url, [
            'getAllAvailableBanks' => false,
            'preferredBanks' => $preferredBanks,
        ]);
        $value = $response->json();

        if (!($value['requestSuccessful'] ?? false)) {
            Log::warning('Monnify linked account creation failed', [
                'user_id' => $user->id,
                'monify_ref' => $user->monify_ref,
                'response' => $value,
            ]);
            return;
        }

        $this->applyMonnifyAccounts($user, $value['responseBody']['accounts'] ?? []);
        $user->save();
    }

    /**
     * Map Monnify's reserved-account response onto the user's bank fields.
     * Fields absent from the response are left untouched (preserves existing values).
     */
    // private function applyMonnifyAccounts(User $user, array $accounts): void
    // {
    //     foreach ($accounts as $account) {
    //         switch ($account['bankCode'] ?? null) {
    //             case '035':
    //                 $user->wema = $account['accountNumber'];
    //                 break;
    //             case '50515':
    //                 $user->rolex = $account['accountNumber'];
    //                 break;
    //             case '232':
    //                 $user->sterlen = $account['accountNumber'];
    //                 break;
    //         }
    //     }
    // }

    // private function getMonnifyAccessToken(AdexKey $adexKey): ?string
    // {
    //     $base64 = base64_encode("{$adexKey->mon_app_key}:{$adexKey->mon_sk_key}");

    //     $response = Http::withHeaders([
    //         'Authorization' => "Basic $base64",
    //     ])->post('https://api.monnify.com/api/v1/auth/login');
    //     $result = $response->json();

    //     return $result['responseBody']['accessToken'] ?? null;
    // }

    // private function generateMonnifyReference(): string
    // {
    //     $code = random_int(100000, 999999);
    //     $rand = random_int(1000, 9999);
    //     $appName = config('app.name');

    //     return "|{$appName}|MONNIFY|{$code}|adex-dev-{$rand}";
    // }
}
