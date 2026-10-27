<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Withdraw;
use App\Models\History;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\AdexKey;
use App\Models\KYCProfile;
use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;

class WithdrawalController extends Controller
{
    public function Transfer(Request $request)
    {
        Log::info('Withdrawal request received', $request->all());

        $validator = Validator::make($request->all(), [
            'accountNumber' => 'required|numeric|digits:10',
            'amount'        => 'required|numeric|min:50|max:1000000',
            'bank'          => 'required|string',
            'narration'     => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status'  => 'fail',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $adexKey = AdexKey::first();
        $setting = Setting::first();
        if ($setting->withdraw != 1) {
            return response()->json([
                'status'  => 'fail',
                'message' => "Withdrawal service is temporarily unavailable. Kindly try again later"
            ], 403);
        }
        $kyc = KYCProfile::where('user_id', auth()->id())->first();
        if (!$kyc) {
            return response()->json([
                'status'  => 'fail',
                'message' => 'Kindly verify your account'
            ], 403);
        }
        if (!$adexKey) {
            return response()->json([
                'status'  => 'fail',
                'message' => 'Service unavailable: Missing API keys',
            ], 503);
        }

        // ✅ Verify bank details BEFORE opening a DB transaction / row lock,
        // so we never hold a lock across a slow external HTTP call.
        try {
            $verifyResponse = Http::timeout(15)->post('https://api.xixapay.com/api/verify/bank', [
                "bank"          => $request->bank,
                "accountNumber" => $request->accountNumber,
            ]);
        } catch (ConnectionException $e) {
            Log::error('Bank verification connection failed', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);
            return response()->json([
                'status'  => 'fail',
                'message' => 'Bank verification service unreachable, please try again',
            ], 503);
        }

        $verifyJson = $verifyResponse->json();
        if (!$verifyResponse->successful()) {
            Log::error('Bank verification failed', [
                'user_id'  => auth()->id(),
                'status'   => $verifyResponse->status(),
                'body'     => $verifyResponse->body(),
            ]);
            return response()->json([
                'status'  => 'fail',
                'message' => 'Bank verification failed',
            ], 400);
        }

        DB::beginTransaction();

        // ✅ Lock user row
        $user = User::where('id', auth()->id())->lockForUpdate()->first();
        if (!$user) {
            DB::rollBack();
            return response()->json([
                'status'  => 'fail',
                'message' => 'User not found',
            ], 404);
        }

        $charges = $adexKey->withdrawal_charges;
        $amount  = (float) $request->amount;
        $total   = $amount + $charges;

        // ✅ Check sufficient balance
        if ($user->bal < $total) {
            DB::rollBack();
            return response()->json([
                'status'  => 'fail',
                'message' => 'Insufficient balance',
            ], 400);
        }

        // ✅ Daily withdrawal limit
        $dailyLimit = (float) $user->withdrawal_limit;
        $todayTotal = (float) Withdraw::where('user_id', $user->id)
            ->where('plan_status', '!=', 2) // exclude failed
            ->whereDate('created_at', Carbon::today())
            ->sum('amount');

        if (($todayTotal + $amount) > $dailyLimit) {
            DB::rollBack();
            return response()->json([
                'status'  => 'fail',
                'message' => 'Daily withdrawal limit of ₦' . number_format($dailyLimit) . ' exceeded',
            ], 400);
        }

        // ✅ Save balances
        $oldBal = $user->bal;
        $newBal = $oldBal - $total;

        // Deduct balance
        $user->update(['bal' => $newBal]);

        // ✅ Generate IDs
        $transId   = bin2hex(random_bytes(10));
        $myTransId = strtoupper(Str::random(12));

        $bankName    = $verifyJson['BankName'] ?? $request->bank;
        $accountName = $verifyJson['AccountName'] ?? '';

        // ✅ Insert Withdraw
        $withdraw = Withdraw::create([
            'user_id'       => $user->id,
            'user_email'    => $user->email,
            'bank_name'     => $bankName,
            'accountName'   => $accountName,
            'accountNumber' => $request->accountNumber,
            'amount'        => $amount,
            'charges'       => $charges,
            'narration'     => $request->narration ?? "Withdrawal to {$request->bank}",
            'plan_status'   => 0,
            'oldbal'        => $oldBal,
            'newbal'        => $newBal,
            'plan_date'     => now(),
            'transid'       => $transId,
            'my_trans_id'   => $myTransId,
        ]);

        // ✅ Insert History
        $history = History::create([
            'username'    => $user->username,
            'user_id'     => $user->id,
            'amount'      => $amount,
            'message'     => 'Withdrawal request to ' . $bankName . ' ' . $request->accountNumber,
            'oldbal'      => $oldBal,
            'newbal'      => $newBal,
            'adex_date'   => now(),
            'plan_status' => 0,
            'transid'     => $transId,
            'role'        => 'withdrawal',
            'my_trans_id' => $myTransId,
        ]);

        DB::commit();
        try {
            $response = Http::timeout(30)->withHeaders([
                'api-key'       => $adexKey->x_akey,
                'Authorization' => 'Bearer ' . $adexKey->x_skey,
            ])->post("https://api.xixapay.com/api/v1/transfer", [
                "businessId"    => $adexKey->x_business_id,
                "amount"        => $amount,
                "bank"          => $request->bank,
                "narration"     => $request->narration,
                "accountNumber" => $request->accountNumber,
            ]);

            $apiResponse = $response->json();
            $withdraw->update(['serverResponse' => $response->body()]);
            if ($response->successful() && isset($apiResponse['status']) && strtolower($apiResponse['status']) === 'success') {
                // ✅ Success
                $withdraw->update(['plan_status' => 1]);
                $history->update([
                    'plan_status' => 1,
                    'message'     => 'Withdrawal of ₦' . number_format($amount) . ' to account '
                        . $request->accountNumber . ' (' . ($accountName ?: 'N/A') . ') '
                        . 'at ' . $bankName . ' was completed successfully.',
                ]);
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Withdrawal of ₦' . number_format($amount) . ' to account '
                        . $request->accountNumber . ' (' . ($accountName ?: 'N/A') . ') '
                        . 'at ' . $bankName . ' was completed successfully.',
                    'data'    => [
                        'transaction_id'  => $transId,
                        'amount'          => $amount,
                        'charges'         => $charges,
                        'account_name'    => $accountName,
                        'account_number'  => $request->accountNumber,
                        'bank'            => $bankName,
                        'balance_before'  => $oldBal,
                        'balance_after'   => $newBal,
                        'today_withdrawn' => $todayTotal + $amount,
                        'daily_limit'     => $dailyLimit,
                    ]
                ], 200);
            }

            Log::error('Xixapay transfer did not report success', [
                'withdraw_id'  => $withdraw->id,
                'my_trans_id'  => $myTransId,
                'http_status'  => $response->status(),
                'api_response' => $apiResponse,
            ]);
            $failureReason = $apiResponse['message'] ?? $apiResponse['error'] ?? 'Transfer failed';
        } catch (ConnectionException $e) {
            Log::error('Xixapay transfer connection failed', [
                'withdraw_id' => $withdraw->id,
                'my_trans_id' => $myTransId,
                'error'       => $e->getMessage(),
            ]);
            $withdraw->update(['serverResponse' => 'ConnectionException: ' . $e->getMessage()]);
            $failureReason = 'Transfer service unreachable';
        }

        // ❌ Failure: Refund user
        DB::beginTransaction();
        $user = User::where('id', $user->id)->lockForUpdate()->first();
        $user->bal += $total;
        $user->save();

        $withdraw->update([
            'plan_status' => 2,
            'narration'   => $withdraw->narration . ' | FAILED: ' . $failureReason,
            'oldbal'      => $user->bal - $total,
            'newbal'      => $user->bal,
        ]);

        $history->update([
            'message'     => 'Refund for failed withdrawal to ' . $bankName . ' ' . $request->accountNumber . ' — ' . $failureReason,
            'plan_status' => 2,
            'oldbal'      => $user->bal - $total,
            'newbal'      => $user->bal,
        ]);

        DB::commit();

        return response()->json([
            'status'  => 'fail',
            'message' => 'Withdrawal failed. Your funds have been refunded.',
        ], 400);
    }
}
