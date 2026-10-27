<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BulkSms;
use App\Models\History;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Models\BulkSmsSelection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BulksmsPurchase extends Controller
{
    public function buy(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->failResponse('Invalid access token. Please log in to continue.');
        }

        $setting = Setting::first();
        $myTransId = bin2hex(random_bytes(8));
        $system  = $request->system;
        $transId   = $system === 'WEBSITE' 
            ? $this->purchase_ref('BILL_') 
            : $request->input('request-id');

        if ($setting->bulksms != 1) {
            return $this->failResponse('Bulk SMS service is currently unavailable. Please try again later.');
        }

        if ($this->transactionExists($transId, $myTransId)) {
            return $this->failResponse('This transaction already exists. Please try again in a few minutes.');
        }
       
        $validator = $this->validateInput($request, $system, $setting);
        if ($validator->fails()) {
            return $this->failResponse($validator->errors()->first());
        }

        $numbers        = explode(',', $request->number);
        $realNumbers    = $this->filterValidNumbers($numbers);
        $wrongNumbers   = array_diff($numbers, $realNumbers);

        if (count($numbers) > 10000) {
            return $this->failResponse('You can send to a maximum of 10,000 numbers at once.');
        }

        if (empty($realNumbers)) {
            return $this->failResponse('No valid phone numbers found. Please check your list.');
        }

        $charges = count($realNumbers) * $setting->bulk_sms;
        
        // Daily limit check
        if ($setting->allow_limit == 1 && $user->kyc == 0) {
            $totalDailySpend = History::where('user_id', $user->id)
                ->whereNotIn('role', ['credit', 'debit', 'upgrade', 'earning'])
                ->where('plan_status', '!=', 2)
                ->where('created_at', '>=', Carbon::today())
                ->sum('amount');

            if (($totalDailySpend + $charges) > $user->user_limit) {
                return $this->failResponse('You have reached your daily transaction limit. Please upgrade your account to continue.', 403);
            }
        }

        DB::beginTransaction();
        $user = User::where('id', $user->id)->lockForUpdate()->first();

        if ($user->bal < $charges) {
            DB::rollBack();
            return $this->failResponse('Insufficient balance. Please fund your wallet. Current balance: ₦' . number_format($user->bal, 2));
        }

        $oldBal = $user->bal;
        $newBal = $oldBal - $charges;
        $user->update(['bal' => $newBal]);

        // Perform vending
        $vendor  = BulkSmsSelection::first();
        $service = $vendor->bulksms;

        // Transaction records
        $historyTransaction = History::create([
            'username'    => $user->username,
            'user_id'     => $user->id,
            'amount'      => $charges,
            'message'     => 'Processing your request to send messages to ' . count($realNumbers) . ' recipient(s).',
            'oldbal'      => $oldBal,
            'newbal'      => $newBal,
            'adex_date'   => now(),
            'plan_status' => 0,
            'transid'     => $transId,
            'role'        => 'bulksms',
            'my_trans_id' => $myTransId,
        ]);

        $bulksmsTransaction = BulkSms::create([
            'username'              => $user->username,
            'user_id'              => $user->id,
            'amount'                => $charges,
            'oldbal'                => $oldBal,
            'newbal'                => $newBal,
            'plan_date'             => now(),
            'transid'               => $transId,
            'my_trans_id'           => $myTransId,
            'plan_status'           => 0,
            'correct_number'        => implode(',', $realNumbers),
            'wrong_number'          => implode(',', $wrongNumbers),
            'total_number'          => count($numbers),
            'total_correct_number'  => count($realNumbers),
            'total_wrong_number'    => count($wrongNumbers),
            'message'               => $request->message,
            'sender_name'           => $request->sender,
            'numbers'               => implode(',', $numbers),
            'vending_system'        => $service,
            'system'                => $system
        ]);

        DB::commit();

        $vendorFunc = Str::startsWith($service, 'adex') ? 'adexHandler' : $service;
        $vendingService = new BulksmsSend();
           if (!method_exists($vendingService, $vendorFunc)) {
           return $this->handleVendingResponse(
            'fail',
            $user,
            $charges,
            $charges,
            $bulksmsTransaction,
            $transId,
            $realNumbers,
            $wrongNumbers,
            $numbers,
            $oldBal,
            $newBal,
            $request,
            $historyTransaction
        );
        }
        $response = $vendingService::$vendorFunc($bulksmsTransaction);

        return $this->handleVendingResponse(
            $response,
            $user,
            $charges,
            $charges,
            $bulksmsTransaction,
            $transId,
            $realNumbers,
            $wrongNumbers,
            $numbers,
            $oldBal,
            $newBal,
            $request,
            $historyTransaction
        );
    }

    private function handleVendingResponse(
        string $result,
        User $user,
        float $originalAmount,
        float $debitedAmount,
        BulkSms $bulkTransaction,
        string $transId,
        array $realNumbers,
        array $wrongNumbers,
        array $allNumbers,
        float $oldBalBeforeDebit,
        float $newBalAfterDebit,
        Request $request,
        History $historyTransaction
    ) {
        DB::beginTransaction();
        $userForUpdate = User::where('id', $user->id)->lockForUpdate()->first();
        $status = 'process';
        $finalBalance = $newBalAfterDebit;

        if ($result === 'success') {
            $status = 'success';
            $bulkTransaction->update(['plan_status' => 1]);
            $historyTransaction->update([
                'plan_status' => 1,
                'message'     => 'Bulk SMS sent successfully.'
            ]);
        } elseif ($result === 'fail') {
            $status = 'fail';
            $userForUpdate->bal += $debitedAmount;
            $userForUpdate->save();
            $finalBalance = $userForUpdate->bal;
            $bulkTransaction->update([
                'plan_status' => 2,
                'newbal'      => $finalBalance
            ]);
            $historyTransaction->update([
                'plan_status' => 2,
                'message'     => 'Bulk SMS sending failed. Amount refunded to your wallet.',
                'newbal'      => $finalBalance
            ]);
        }

        DB::commit();

        return $this->formatResponse(
            $status,
            $originalAmount,
            $finalBalance,
            $oldBalBeforeDebit,
            now(),
            $transId,
            $realNumbers,
            $wrongNumbers,
            $allNumbers,
            $request
        );
    }

  private function formatResponse(
    string $status,
    float $charges,
    float $newBal,
    float $oldBal,
    string $planDate,
    string $transId,
    array $realNumbers,
    array $wrongNumbers,
    array $allNumbers,
    Request $request
) {
    // Choose the message based on transaction status
    $statusMessage = match ($status) {
        'success' => 'Bulk SMS sent successfully to ' . count($realNumbers) . ' recipient(s).',
        'fail'    => 'Bulk SMS sending failed. Amount has been refunded to your wallet.',
        default   => 'Your request to send messages to ' . count($realNumbers) . ' recipient(s) is being processed.'
    };

    return response()->json([
        'amount'               => $charges,
        'newbal'               => $newBal,
        'oldbal'               => $oldBal,
        'plan_date'            => $planDate,
        'request-id'           => $transId,
        'transid'           => $transId,
        'status'               => $status,
        'correct_number'       => implode(',', $realNumbers),
        'wrong_number'         => implode(',', $wrongNumbers),
        'total_number'         => count($allNumbers),
        'total_correct_number' => count($realNumbers),
        'total_wrong_number'   => count($wrongNumbers),
        'message'              => $statusMessage,
        'sender_name'          => $request->sender,
        'numbers'              => implode(',', $allNumbers)
    ]);
}


    private function filterValidNumbers(array $numbers): array
    {
        return array_values(array_filter($numbers, function ($num) {
            $num = trim($num);
            $startsWith0 = substr($num, 0, 1) == '0';
            $startsWith234 = substr($num, 0, 3) == '234';
            $lengthOk = in_array(strlen($num), [11, 13]);
            return ($startsWith0 xor $startsWith234) && $lengthOk && is_numeric($num);
        }));
    }

    private function transactionExists($transId, $myTransId): bool
    {
        return BulkSms::where('transid', $transId)->orWhere('my_trans_id', $myTransId)->exists()
            || History::where('transid', $transId)->orWhere('my_trans_id', $myTransId)->exists();
    }

    private function validateInput($request, $system, $setting)
    {
        $rules = [
            'number'  => 'required|string',
            'message' => "required|string|min:1|max:{$setting->bulk_length}",
            'sender'  => 'required|string|min:1|max:10',
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
}
