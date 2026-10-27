<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Setting;
use App\Models\Network;
use App\Models\OtherApi;
use App\Models\Cash;
use App\Models\CashDiscount;
use App\Models\History;
use Carbon\Carbon;

class AirtimeCash extends Controller
{
    public function Convert(Request $request)
    {
        $user = auth()->user(); 
        if (!$user) {
            return $this->failResponse('User not authenticated', 403);
        }

        $setting = Setting::first();
        $system  = $request->system;

        // Validate input
        $validator = $this->validateInput($request, $system, $setting);
        if ($validator->fails()) {
            return $this->failResponse($validator->errors()->first(), 422);
        }

        $step    = (int) $request->step;
        $phone   = $request->phone;
        $network = Network::where('plan_id', $request->network)->first();

        if (!$network) {
            return $this->failResponse('Invalid network selected', 404);
        }
        if ($network->cash != 1) {
            return $this->failResponse($network->network . ' airtime-to-cash service is not yet available.', 403);   
        }
        if (!in_array($network->network, ['MTN', 'AIRTEL'])) {
            return $this->failResponse($network->network . ' airtime-to-cash service is not yet available.', 403);
        }
        if(is_null(auth()->user()->bvn)){
            return $this->failResponse('Your account requires KYC verification. Kindly complete your verification to proceed.');
        }

        // Check daily transaction limit
        $totalDailySpend = Cash::where('user_id', $user->id)
            ->whereNotIn('plan_status', [0, 2])
            ->where('created_at', '>=', Carbon::today())
            ->sum('amount');

        if ($totalDailySpend > $user->airtime_to_cash_limit) {
            return $this->failResponse('You have reached your daily transaction limit. Kindly message the Admin to upgrade your account', 403);
        }

        // Step handling
        if ($step === 1) {
            return $this->FirstStep($phone, $network);
        }

        if ($step === 2) {
            if (empty($request->data)) {
                return $this->failResponse('The verification data is missing.', 403);
            }
            if (empty($request->otp)) {
                return $this->failResponse('Please enter the OTP.', 403);
            }
            return $this->SecondStep($phone, $network, $request->otp, $request->data);
        }

        if ($step === 3) {
            if (empty($request->data)) {
                return $this->failResponse('The verification data is missing.', 403);
            }
            if (empty($request->share_pin) || strlen($request->share_pin) !== 4) {
                return $this->failResponse('Share PIN must be exactly 4 digits.', 403);
            }
            if (empty($request->amount)) {
                return $this->failResponse('The amount is required.', 403);
            }
            return $this->thirdStep(
                $phone,
                $network,
                (float) $request->amount,
                (string) $request->data,
                (string) $request->share_pin,
                $system
            );
        }

        return $this->failResponse('Invalid step selected. Please try again.', 400);
    }

    private function thirdStep(string $phone, Network $network, float $amount, string $data, string $share_pin, string $system)
    {
        if (!is_numeric($amount)) {
            return $this->failResponse('The amount must be a number.', 422);
        }
        if ($amount < 50) {
            return $this->failResponse('The minimum amount you can convert is ₦50.', 422);
        }
        if ($amount > 10000) {
            return $this->failResponse('The maximum amount you can convert is ₦10,000.', 422);
        }

        $cashDiscount = CashDiscount::first();
        if (!$cashDiscount) {
            return $this->failResponse('Cash discount configuration missing. Please contact support.', 500);
        }

        $receiver_number = $cashDiscount[strtolower($network->network) . '_number'] ?? null;
        if (!$receiver_number) {
            return $this->failResponse('Receiver number not configured for this network.', 500);
        }

        $request_id  = $this->purchase_ref('AIRTIME_2_Cash_');
        $myTransId   = bin2hex(random_bytes(8));
        $user        = auth()->user();

        $discountRate  = $cashDiscount[strtolower($network->network)] ?? 0;
        $amount_credit = ($amount / 100) * $discountRate;

        $transaction = Cash::create([
            'username'        => $user->username,
            'user_id'         => $user->id,
            'amount'          => $amount,
            'amount_credit'   => $amount_credit,
            'newbal'          => $user->bal,
            'oldbal'          => $user->bal,
            'transid'         => $request_id,
            'my_trans_id'     => $myTransId,
            'network'         => $network->network,
            'payment_type'    => 'WALLET',
            'plan_status'     => 0,
            'plan_date'       => now(),
            'system'          => $system,
            'sender_number'   => $phone,
            'receiver_number' => $receiver_number,
            'vending_system'  => 'quicklysim',
            'vending_url'     => 'https://quicklysim.com/api/cash',
            'request_time'    => now()
        ]);

        $history = History::create([
            'username'   => $user->username,
            'user_id'    => $user->id,
            'amount'     => $amount_credit,
            'message'    => 'Airtime worth ₦' . number_format($amount) . ' to ' . $receiver_number . ' from ' . $phone . ' is being processed.',
            'oldbal'     => $user->bal,
            'newbal'     => $user->bal,
            'adex_date'  => now(),
            'plan_status'=> 0, 
            'transid'    => $request_id,
            'role'       => 'credit',
            'my_trans_id'=> $myTransId,
        ]);

        $payload = [
            'network'        => $network->adex_id,
            'phone'          => $phone,
            'receiver_number'=> $receiver_number,
            'data'           => $data,
            'step'           => 3,
            'share_pin'      => $share_pin,
            'request-id'     => $myTransId,
            'amount'         => $amount
        ];

        $response = $this->sendRequest($payload);

        $transaction->response_time   = now();
        $transaction->ServerResponse  = json_encode($response);
        $transaction->save();

        if ($response && $response['status'] === 'success') {
            DB::beginTransaction();
                $user = User::where('id', $user->id)->lockForUpdate()->first();
                $oldbal = $user->bal;
                $user->bal += $amount_credit;
                $user->save();

                $transaction->newbal      = $user->bal;
                $transaction->oldbal      = $oldbal;
                $transaction->plan_status = 1;
                $transaction->save();

                $history->plan_status = 1;
                $history->oldbal      = $oldbal;
                $history->newbal      = $user->bal;
                $history->save();

                DB::commit();

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Airtime transfer successful.',
                    'data'    => [
                        'transid' => $myTransId,
                        'sender'   => $phone,
                        'receiver' => $receiver_number,
                        'response' => $response['message'] ?? 'Completed',
                        'amount'   => number_format($amount),
                        'note'     => '₦' . number_format($amount) . ' has been successfully shared with ' . $receiver_number,
                    ]
                ], 200);
        }
                $transaction->plan_status = 2;
                $transaction->save();

                $history->plan_status = 2;
                $history->save();

        return $this->failResponse($response['message'] ?? 'Unable to complete airtime transfer. Please restart the process from Step 1');
    }

    private function SecondStep(string $phone, Network $network, string $otp, string $data)
    {
        if ($network->network === 'AIRTEL' && strlen($otp) != 4) {
            return $this->failResponse('Airtel OTP must be 4 digits');
        }
        if ($network->network === 'MTN' && strlen($otp) != 6) {
            return $this->failResponse('MTN OTP must be 6 digits');
        }

        $payload = [
            'network' => $network->adex_id,
            'phone'   => $phone,
            'otp'     => $otp,
            'data'    => $data,
            'step'    => 2
        ];

        $response = $this->sendRequest($payload);
        if ($response && $response['status'] === 'success') {
            return response()->json([
                'status'  => 'success',
                'data'    => $response['data'] ?? [],
                'balance' => $response['balance'] ?? 'unknown'
            ]);
        }
        return $this->failResponse($response['message'] ?? 'Invalid OTP.', 403);
    }

    private function FirstStep(string $phone, Network $network)
    {
        $payload = [
            'network' => $network->adex_id,
            'phone'   => $phone,
            'step'    => 1
        ];

        $response = $this->sendRequest($payload);

        if ($response && $response['status'] === 'success') {
            return response()->json([
                'status'  => 'success',
                'message' => "OTP sent successfully to $phone",
                'data'    => $response['data'] ?? []
            ]);
        }  

        return $this->failResponse($response['message'] ?? 'Failed to deliver the OTP. Kindly try again in a few moments.', 403);
    }

    private function sendRequest(array $payload)
    {
        $quicklySim = OtherApi::first();
        if (
            is_null($quicklySim) || 
            empty($quicklySim->quickly_email) || 
            empty($quicklySim->quickly_pass)
        ) {
            return ['status' => 'fail', 'message' => 'Unable to send request'];
        }

        $accessToken = base64_encode($quicklySim->quickly_email . ":" . $quicklySim->quickly_pass);

        // Get Access Token
        $response = Http::withHeaders([
            "Authorization" => "Basic " . $accessToken
        ])->post('https://quicklysim.com/api/user');

        if (!$response->successful() || empty($response->json('AccessToken'))) {
            return ['status' => 'fail', 'message' => 'Unable to send request'];
        }

        $access_token = $response->json('AccessToken');

        // Send request to quicklysim
        $PResponse = Http::withHeaders([
            "Authorization" => "Token $access_token",
            "Content-Type"  => "application/json"
        ])->post('https://quicklysim.com/api/cash', $payload);

       return $PResponse->json();
    }

    private function validateInput(Request $request, $system, $setting)
    {
        $rules = [
            'network' => 'required|exists:network,plan_id',
            'phone'   => 'required|numeric|digits:11',
            'step'    => 'required|numeric|in:1,2,3',
        ];

        return Validator::make($request->all(), $rules);
    }

    private function failResponse(string $message, int $status = 403): \Illuminate\Http\JsonResponse
    {
        return response()->json(['status' => 'fail', 'message' => $message], $status);
    }

    private function validatePin(User $user, string $pin): bool
    {
        return User::where(['id' => $user->id, 'pin' => $pin])->exists();
    }
}
