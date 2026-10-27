<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Bill;
use App\Models\History;
use App\Models\Setting;
use App\Models\User;
use App\Models\Block;
use App\Models\BillSelection;
use App\Models\BillPlan;
use App\Models\BillCharge;

class BillPurchase extends Controller
{
    public function Buy(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->failResponse('User not authenticated', 403);
        }

        $setting = Setting::first();
        $charge  = BillCharge::first();
        $system  = $request->system;

        $myTransId = bin2hex(random_bytes(8));
        $transId   = $system === 'WEBSITE' 
            ? $this->purchase_ref('BILL_') 
            : $request->input('request-id');

        // Validate input
        $validator = $this->validateInput($request, $system, $setting);
        if ($validator->fails()) {
            return $this->failResponse($validator->errors()->first(), 403);
        }

        // Pin validation
        if ($setting->allow_pin && $system === 'WEBSITE' && !$this->validatePin($user, $request->pin)) {
            return $this->failResponse('Invalid Transaction Pin', 403);
        }

        // Duplicate transaction check
        if ($this->transactionExists($transId, $myTransId)) {
            return $this->failResponse('Request ID Exists Before', 403);
        }

        // Blocked meter check
        if (Block::where('number', $request->meter_number)->exists()) {
            return $this->failResponse('Meter Number Blocked', 403);
        }

        $BillPlan = BillPlan::where(['plan_id' => $request->disco, 'plan_status' => 1])->first();
        if (!$BillPlan) {
            return $this->failResponse('Disco Not Found/Not Active', 403);
        }

        if ($setting->bill != 1) {
            return $this->failResponse('Electricity Bill Not Available Right Now', 403);
        }

        // Balance validation
        if (!is_numeric($user->bal)) {
            return $this->failResponse('Invalid account balance', 403);
        }

        // Discount
        $discount = 0;
        if ($charge && $charge->direct == 1) {
            $discount = ($request->amount / 100) * $BillPlan->commission;
        }
        $amount = $request->amount - $discount;

        // Daily limit check
        if ($setting->allow_limit == 1 && $user->kyc == 0) {
            $totalDailySpend = History::where('user_id', $user->id)
                ->whereNotIn('role', ['credit', 'debit', 'upgrade', 'earning'])
                ->where('plan_status', '!=', 2)
                ->where('created_at', '>=', Carbon::today())
                ->sum('amount');

            if (($totalDailySpend + $amount) > $user->user_limit) {
                return $this->failResponse('You have reached your daily transaction limit. Kindly upgrade your account.', 403);
            }
        }

        DB::beginTransaction();
        $user = User::where('id', $user->id)->lockForUpdate()->first();
        if ($user->bal < $amount) {
            DB::rollBack();
            return $this->failResponse('Insufficient Balance. Wallet: ₦' . number_format($user->bal, 2), 403);
        }

        $billselection = BillSelection::first();
        if (!$billselection || !$billselection->bill) {
            DB::rollBack();
            return $this->failResponse('Vendor method not set', 403);
        }

        $vendorFunc = Str::startsWith($billselection->bill, 'adex') ? 'adexHandler' : $billselection->bill;

        // Meter verification
        $adm = new MeterSend();
        $sending_data = [
            'disco'         => $request->disco,
            'meter_type'    => strtolower($request->meter_type),
            'meter_number'  => strtolower($request->meter_number),
            'method' => $billselection->bill
        ];
        $customer_name = $adm->$vendorFunc($sending_data);
        if (empty($customer_name)) {
            DB::rollBack();
            return $this->failResponse('Invalid Meter Number', 403);
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
            'message'     => 'Transaction in process: ' . strtoupper($BillPlan->disco_name) . ' ' . strtoupper($request->meter_type) . ' ₦' . $request->amount . ' to ' . $request->meter_number,
            'oldbal'      => $oldBal,
            'newbal'      => $newBal,
            'adex_date'   => now(),
            'plan_status' => 0,
            'transid'     => $transId,
            'role'        => 'bill',
            'my_trans_id' => $myTransId,
        ]);

        // Save bill transaction
        $billTransaction = Bill::create([
            'username'       => $user->username,
            'user_id'        => $user->id,
            'amount'         => $request->amount,
            'charges'        => $discount,
            'disco_name'     => $BillPlan->disco_name,
            'meter_number'   => $request->meter_number,
            'plan_status'    => 0,
            'meter_type'     => strtoupper($request->meter_type),
            'plan_date'      => now(),
            'transid'        => $transId,
            'my_trans_id'    => $myTransId,
            'customer_name'  => $customer_name,
            'system'         => $system,
            'oldbal'         => $oldBal,
            'newbal'         => $newBal,
            'vending_system' => $billselection->bill
        ]);

        DB::commit();

        // Vending
        $billvend = new BillSend();
        if (!method_exists($billvend, $vendorFunc)) {
            Log::error("BillSend method {$vendorFunc} does not exist.");
            return $this->handleVendingResponse('fail', $user, $request->amount, $amount, $billTransaction, $transId, $request->meter_number, $oldBal, $newBal, $system, $BillPlan, $history);
        }

        $responseFromVendor = $billvend->$vendorFunc($billTransaction, $BillPlan);

        return $this->handleVendingResponse(
            $responseFromVendor,
            $user,
            $request->amount,
            $amount,
            $billTransaction,
            $transId,
            $request->meter_number,
            $oldBal,
            $newBal,
            $system,
            $BillPlan,
            $history
        );
    }

    private function handleVendingResponse(
        string $result,
        User $user,
        float $originalAmount,
        float $debitedAmount,
        Bill $billTransaction,
        string $transId,
        string $meter_number,
        float $oldBalBeforeDebit,
        float $newBalAfterDebit,
        string $system,
        BillPlan $BillPlan,
        History $historyTransaction
    ): \Illuminate\Http\JsonResponse {
        $billTransaction->refresh();
        $status = 0; 
        $message = '';
        $finalUserBalance = $newBalAfterDebit; 

        DB::beginTransaction();
        $userForUpdate = User::where('id', $user->id)->lockForUpdate()->first();

        if ($result === 'success') {
            $status = 1;
            $message = "Successfully purchased ₦" . number_format($originalAmount, 2) . " electricity for {$BillPlan->disco_name} ({$meter_number}).";
            $billTransaction->update(['plan_status' => $status]);
            $historyTransaction->update(['plan_status' => $status, 'message' => $message]);

        } elseif ($result === 'fail') {
            $status = 2;
            $message = "Failed purchase of ₦" . number_format($originalAmount, 2) . " electricity for {$BillPlan->disco_name} ({$meter_number}).";

            $userForUpdate->bal += $debitedAmount;
            $userForUpdate->save();
            $finalUserBalance = $userForUpdate->bal;

            $message .= " Amount of ₦" . number_format($debitedAmount, 2) . " refunded.";
            $historyTransaction->update([
                'message'     => $message,
                'newbal'      => $finalUserBalance,
                'oldbal'      => $finalUserBalance,
                'plan_status' => $status,
            ]);
            $billTransaction->update([
                'plan_status' => $status,
                'newbal'      => $finalUserBalance,
                'oldbal'      => $finalUserBalance,
            ]);
        } else {
            $status = 0;
            $message = "Electricity purchase in process: ₦" . number_format($originalAmount, 2) . " for {$BillPlan->disco_name} ({$meter_number}).";
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
                    'meter_number'   => $meter_number,
                    'disco_name'     => $BillPlan->disco_name,
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
            'disco_name'     => $BillPlan->disco_name,
            'request-id'     => $transId,
            'transid' => $transId,
            'amount'         => $originalAmount,
            'amount_charged' => $debitedAmount,
            'status'         => ($status === 1) ? 'success' : (($status === 2) ? 'fail' : 'process'),
            'message'        => $message,
            'meter_number'   => $meter_number,
            'oldbal'         => $oldBalBeforeDebit,
            'newbal'         => $finalUserBalance,
            'system'         => $system,
            'token'          => $billTransaction->token,
            'wallet_vending' => 'wallet',
        ]);
    }

    private function transactionExists($transId, $myTransId): bool
    {
        return Bill::where('transid', $transId)->orWhere('my_trans_id', $myTransId)->exists()
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
            'disco'         => 'required',
            'meter_number'  => 'required',
            'meter_type'    => 'required',
            'amount'        => ['required', 'numeric', 'integer', 'gt:0'],
            'request-id'    => 'nullable|unique:bill,transid'
        ];

        $charge = BillCharge::first();
        if ($charge) {
            $rules['amount'][] = 'min:' . $charge->bill_min;
            $rules['amount'][] = 'max:' . $charge->bill_max;
        }

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
