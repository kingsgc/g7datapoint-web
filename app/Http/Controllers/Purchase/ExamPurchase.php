<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ExamId;
use App\Models\ExamSelection;
use App\Models\History;
use App\Models\Exam;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Setting;
use App\Models\User;
use App\Models\CableLock;
use App\Models\ResultCharge;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class ExamPurchase extends Controller
{
    public function ExamPurchase(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->failResponse('User authentication required.', 403);
        }

        $setting = Setting::first();
        $system  = $request->system;

        $myTransId = bin2hex(random_bytes(8));
        $transId   = $system === 'WEBSITE'
            ? $this->purchase_ref('EXAM_')
            : $request->input('request-id');

        // Validate input
        $validator = $this->validateInput($request, $system, $setting);
        if ($validator->fails()) {
            return $this->failResponse($validator->errors()->first(), 403);
        }

        // Pin validation
        if ($setting->allow_pin && $system === 'WEBSITE' && !$this->validatePin($user, $request->pin)) {
            return $this->failResponse('Invalid transaction PIN provided.', 403);
        }

        // Duplicate transaction check
        if ($this->transactionExists($transId, $myTransId)) {
            return $this->failResponse('Duplicate request detected. This transaction has already been processed.', 403);
        } 

        // Exam validation
        $ExamID = ExamId::find($request->exam);
        if (!$ExamID) {
            return $this->failResponse('Invalid Exam ID selected.', 403);
        }

        $exam_name = strtolower($ExamID->exam_name);

        // Availability check
        $exam_lock = CableLock::first();
        if ($exam_lock && $exam_lock->$exam_name != 1) {
            return $this->failResponse(strtoupper($exam_name) . " is currently unavailable. Please try again later.", 403);
        }

        // Balance validation
        if (!is_numeric($user->bal)) {
            return $this->failResponse('Invalid account balance.', 403);
        }

        $resultCharge = ResultCharge::first();
        $amount = $resultCharge->$exam_name * $request->quantity;

        // Daily limit check
        if ($setting->allow_limit == 1 && $user->kyc == 0) {
            $totalDailySpend = History::where('user_id', $user->id)
                ->whereNotIn('role', ['credit', 'debit', 'upgrade', 'earning'])
                ->where('plan_status', '!=', 2)
                ->where('created_at', '>=', Carbon::today())
                ->sum('amount');

            if (($totalDailySpend + $amount) > $user->user_limit) {
                return $this->failResponse('Daily transaction limit reached. Please contact support to upgrade your account.', 403);
            }
        }

        DB::beginTransaction();
        $user = User::where('id', $user->id)->lockForUpdate()->first();
        if ($user->bal < $amount) {
            DB::rollBack();
            return $this->failResponse('Insufficient wallet balance. Available balance: ₦' . number_format($user->bal, 2), 403);
        }

        $examselection = ExamSelection::first();
        if (!$examselection) {
            DB::rollBack();
            return $this->failResponse('Exam vendor configuration not found.', 403);
        }

        $vendorFunc = $examselection->$exam_name;
        if (!$vendorFunc) {
            DB::rollBack();
            return $this->failResponse('Vendor method not configured for ' . strtoupper($exam_name) . '.', 403);
        }

        if (Str::startsWith($vendorFunc, 'adex')) {
            $vendorFunc = 'adexHandler';
        }

        // Deduct balance
        $oldBal = $user->bal;
        $newBal = $oldBal - $amount;
        $user->bal = $newBal;
        $user->save();

        // Save history
        $history = History::create([
            'username'    => $user->username,
            'user_id'     => $user->id,
            'amount'      => $amount,
            'message'     => 'Processing purchase of ' . strtoupper($exam_name) . ' exam pin(s) worth ₦' . number_format($amount, 2) . ' (' . $request->quantity . ' quantity).',
            'oldbal'      => $oldBal,
            'newbal'      => $newBal,
            'adex_date'   => now(),
            'plan_status' => 0,
            'transid'     => $transId,
            'role'        => 'exam',
            'my_trans_id' => $myTransId,
        ]);

        // Save exam transaction
        $examTransaction = Exam::create([
            'username'        => $user->username,
            'user_id'         => $user->id,
            'amount'          => $amount,
            'plan_status'     => 0,
            'quantity'        => $request->quantity,
            'plan_date'       => now(),
            'transid'         => $transId,
            'my_trans_id'     => $myTransId,
            'exam_name'       => strtoupper($exam_name),
            'system'          => $system,
            'oldbal'          => $oldBal,
            'newbal'          => $newBal,
            'vending_system'  => $examselection->$exam_name
        ]);

        DB::commit();

        $examSender = new ExamSend();
        if (!method_exists($examSender, $vendorFunc)) {
            Log::error("ExamSend method {$vendorFunc} does not exist.");
            $this->handleVendingResponse('fail', $user, $amount, $amount, $examTransaction, $transId, $request->quantity, $oldBal, $newBal, $system, $history);
            return $this->failResponse('Internal vending system error. Please contact support.', 500);
        }

        $responseFromVendor = $examSender->$vendorFunc($examTransaction);

        return $this->handleVendingResponse(
            $responseFromVendor,
            $user,
            $amount,
            $amount,
            $examTransaction,
            $transId,
            $request->quantity,
            $oldBal,
            $newBal,
            $system,
            $history
        );
    }

    private function handleVendingResponse(
        string $result,
        User $user,
        float $originalAmount,
        float $debitedAmount,
        Exam $examTransaction,
        string $transId,
        string $quantity,
        float $oldBalBeforeDebit,
        float $newBalAfterDebit,
        string $system,
        History $historyTransaction
    ): \Illuminate\Http\JsonResponse {
        $examTransaction->refresh();
        $status = 0;
        $message = '';
        $finalUserBalance = $newBalAfterDebit;
        DB::beginTransaction();
        $userForUpdate = User::where('id', $user->id)->lockForUpdate()->first();
        if ($result === 'success') {
            $status = 1;
            $message = "Purchase successful! ₦" . number_format($originalAmount, 2) . " for {$examTransaction->exam_name} exam pin(s) ({$quantity} quantity).";
            $examTransaction->update(['plan_status' => $status]);
            $historyTransaction->update(['plan_status' => $status, 'message' => $message]);

        } elseif ($result === 'fail') {
            $status = 2;
            $message = "Purchase failed for {$examTransaction->exam_name} exam pin(s) ({$quantity} quantity) costing ₦" . number_format($originalAmount, 2) . ".";
            $userForUpdate->bal += $debitedAmount;
            $userForUpdate->save();
            $finalUserBalance = $userForUpdate->bal;

            $message .= " Refund of ₦" . number_format($debitedAmount, 2) . " has been processed.";
            $historyTransaction->update([
                'message'     => $message,
                'newbal'      => $finalUserBalance,
                'oldbal'      => $finalUserBalance,
                'plan_status' => $status,
            ]);
            $examTransaction->update([
                'plan_status' => $status,
                'newbal'      => $finalUserBalance,
                'oldbal'      => $finalUserBalance,
            ]);
        } else {
            $status = 0;
            $message = "Purchase in progress for {$examTransaction->exam_name} exam pin(s) ({$quantity} quantity) worth ₦" . number_format($originalAmount, 2) . ".";
        }

        DB::commit();

        // Webhook
        if ($user->webhook) {
            try {
                $payload = [
                    'status'         => ($status === 1) ? 'success' : (($status === 2) ? 'fail' : 'process'),
                    'request-id'     => $transId,
                    'amount'         => $originalAmount,
                    'amount_charged' => $debitedAmount,
                    'quantity'       => $quantity,
                    'exam_name'      => $examTransaction->exam_name,
                    'message'        => $message,
                    'old_balance'    => $oldBalBeforeDebit,
                    'new_balance'    => $finalUserBalance,
                ];
                $this->sendWebhook($user->webhook, $payload);
            } catch (\Throwable $e) {
                Log::error("Webhook failed for TransID {$transId}: " . $e->getMessage());
            }
        }

        return response()->json([
            'exam_name'     => $examTransaction->exam_name,
            'request-id'    => $transId,
            'transid'    => $transId,
            'amount'        => $originalAmount,
            'amount_charged'=> $debitedAmount,
            'status'        => ($status === 1) ? 'success' : (($status === 2) ? 'fail' : 'process'),
            'message'       => $message,
            'quantity'      => $quantity,
            'oldbal'        => $oldBalBeforeDebit,
            'newbal'        => $finalUserBalance,
            'system'        => $system,
            'token'         => $examTransaction->purchase_code,
            'pin'           => $examTransaction->purchase_code,
            'wallet_vending'=> 'wallet',
        ]);
    }

    private function transactionExists($transId, $myTransId): bool
    {
        return Exam::where('transid', $transId)->orWhere('my_trans_id', $myTransId)->exists()
            || History::where('transid', $transId)->orWhere('my_trans_id', $myTransId)->exists();
    }

    private function sendWebhook(string $url, array $payload)
    {
        try {
            Http::timeout(10)->post($url, $payload);
        } catch (\Throwable $e) {
            Log::error("Failed to send webhook to {$url}: " . $e->getMessage());
        }
    }

    private function validateInput($request, $system, $setting)
    {
        $rules = [
            'exam' => 'required',
            'quantity' => 'required|numeric|integer|not_in:0|gt:0|min:1|max:5',
        ];
        if ($system === 'WEBSITE' && $setting->allow_pin == 1) {
            $rules['pin'] = 'required|numeric|digits:4';
        }
        return Validator::make($request->all(), $rules);
    }

    private function failResponse(string $message, int $status = 403)
    {
        return response()->json(['status' => 'fail', 'message' => $message], $status);
    }

    private function validatePin($user, $pin): bool
    {
        return User::where(['id' => $user->id, 'pin' => $pin])->exists();
    }
}
