<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Setting;
use App\Models\History;
use App\Models\Notif;

class BonusTransfer extends Controller
{
    public function convert(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->failResponse('You must be logged in to perform this action.', 403);
        }

        $setting = Setting::first();
        $system = $request->system;
        $amount = $request->amount;
        $myTransId = bin2hex(random_bytes(8));
        $transId = $this->purchase_ref('Earning_');

        // Validate input
        $validator = $this->validateInput($request, $system, $setting);
        if ($validator->fails()) {
            return $this->failResponse($validator->errors()->first(), 403);
        }

        // Pin validation
        if ($setting->allow_pin && $system === 'WEBSITE' && !$this->validatePin($user, $request->pin)) {
            return $this->failResponse('Invalid transaction PIN.', 403);
        }

        // Check if referral bonus transfer is enabled
        if ($setting->referral != 1) {
            return $this->failResponse('Bonus transfer is currently unavailable. Please try again later.', 403);
        }

        // Validate numeric balance
        if (!is_numeric($user->refbal)) {
            return $this->failResponse('Invalid earning balance detected. Please contact support.', 403);
        }

        DB::beginTransaction();

        // Lock user record for safe update
        $user = User::where('id', $user->id)->lockForUpdate()->first();

        if ($user->refbal < $amount) {
            DB::rollBack();
            return $this->failResponse(
                'Insufficient earning balance. Available: ₦' . number_format($user->refbal, 2),
                403
            );
        }

        // Deduct from earning balance and add to main wallet
        $oldBal = $user->refbal;
        $newBal = $oldBal - $amount;
        $user->refbal = $newBal;
        $user->bal += $amount;
        $user->save();

        // Log transaction
        History::create([
            'username'    => $user->username,
            'user_id'     => $user->id,
            'amount'      => $amount,
            'message'     => 'You have successfully transferred ₦' . number_format($amount, 2) . ' from your earning balance to your main wallet.',
            'oldbal'      => $oldBal,
            'newbal'      => $newBal,
            'adex_date'   => now(),
            'plan_status' => 1,
            'transid'     => $transId,
            'role'        => 'earning',
            'my_trans_id' => $myTransId,
        ]);

        // Send notification
        Notif::create([
            'username' => $user->username,
            'title'    => 'Earning Transfer',
            'message'  => 'You have successfully transferred ₦' . number_format($amount, 2) . ' from your earning balance to your main wallet.',
            'date'     => now(),
            'adex'     => 0,
            'user_id'  => $user->id
        ]);

        DB::commit();

        return response()->json([
            'status'  => 'success',
            'transid' => $transId,
            'request-id' => $transId,
            'message' => 'You have successfully transferred ₦' . number_format($amount, 2) . ' from your earning balance to your main wallet.',
        ]);
    }

    private function validateInput($request, $system, $setting)
    {
        $rules = [
            'amount' => ['required', 'numeric', 'integer', 'not_in:0', 'gt:0', 'min:' . $setting->earning_min]
        ];

        if ($system === 'WEBSITE' && $setting->allow_pin == 1) {
            $rules['pin'] = 'required|numeric|digits:4';
        }

        return Validator::make($request->all(), $rules);
    }

    private function failResponse(string $message, int $status = 403)
    {
        return response()->json([
            'status'  => 'fail',
            'message' => $message
        ], $status);
    }

    private function validatePin($user, $pin): bool
    {
        return User::where(['id' => $user->id, 'pin' => $pin])->exists();
    }
}
