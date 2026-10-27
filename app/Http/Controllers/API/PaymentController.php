<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\AdexKey;
use App\Models\BankTransfer;
use App\Models\Deposit;
use App\Models\General;
use App\Models\History;
use App\Models\Notif;
use App\Models\Request as ModelsRequest;
use App\Models\Setting;
use App\Models\User;
use App\Models\KYCProfile;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{

    public function Xixapay(Request $request)
    {
        $adexKey = AdexKey::first();
        $payload = $request->getContent();
        $secret = $adexKey->x_skey;
        $xixapay_signature = $request->header('xixapay');
        $hashkey = hash_hmac('sha256', $payload, $secret);
        if ($xixapay_signature !== $hashkey) {
            return response()->json('Unknown source', 403);
        }
        $data = json_decode($payload, true);
        $status = $data['notification_status'];
        $amount_paid = floatval($data['amount_paid']);
        $reference = $data['transaction_id'];
        $customer_email = $data['customer']['email'];
        if (Deposit::where('monify_ref', $reference)->exists()) {
            return response()->json('Transaction Ref Exists', 200);
        }
        $user = User::where(['email' => $customer_email, 'status' => 1])->first();
        if (!$user) {
            return response()->json('Unable to find user', 403);
        }
        $setting = Setting::first();
        $type = strtolower($setting->xixapay_charge_type ?? 'flat');
        $chargeValue = (float) $setting->xixapay_charge_value;
        $cap = (float) $setting->xixapay_charge_cap;
        if ($type === 'percentage') {
            $charges = ($amount_paid / 100) * $chargeValue;
            if ($cap > 0 && $charges > $cap) {
                $charges = $cap;
            }
        } else {
            $charges = $chargeValue;
        }
        $credit = $amount_paid - $charges;
        $transid = $this->purchase_ref('AUTOMATED_');
        DB::beginTransaction();
        $user = User::where('id', $user->id)->lockForUpdate()->first();
        // Double-check reference inside transaction
        if (Deposit::where('monify_ref', $reference)->exists()) {
            DB::rollBack();
            return response()->json('Transaction Ref Exists', 200);
        }
        // Insert deposit
        Deposit::create([
            'user_id'     => $user->id,
            'username'    => $user->username,
            'amount'      => $amount_paid,
            'oldbal'      => $user->bal,
            'newbal'      => $user->bal + $credit,
            'wallet_type' => 'User Wallet',
            'type'        => 'Automated Bank Transfer',
            'credit_by'   => 'Xixapay Automated Bank Transfer',
            'date'        => now(),
            'status'      => 1,
            'transid'     => $transid,
            'charges'     => $charges,
            'monify_ref'  => $reference
        ]);
        // Update user balance
        $user->bal += $credit;
        $user->save();
        // Notification
        Notif::create([
            'user_id'  => $user->id,
            'username' => $user->username,
            'message'  => 'Account Credited By Automated Bank Transfer ₦' . number_format($credit, 2),
            'date'     => now(),
            'title'    => 'Account credited',
            'adex'     => 0
        ]);
        // History
        History::create([
            'user_id'   => $user->id,
            'username'  => $user->username,
            'amount'    => $credit,
            'message'   => 'Account Credited By Automated Bank Transfer ₦' . number_format($credit, 2),
            'oldbal'    => $user->bal - $credit,
            'newbal'    => $user->bal,
            'adex_date' => $this->system_date(),
            'plan_status' => 1,
            'transid'   => $transid,
            'my_trans_id' => $transid,
            'role'      => 'credit'
        ]);
        // Referral
        $this->handleReferral($user, $credit);
        DB::commit();
        return response()->json('ok');
    }
     public function Billstack(Request $request)
    {
        // $res = json_decode(file_get_contents("php://input"), true);
        $res = $request->json()->all();
        $head = json_encode(getallheaders());
        $unhead = json_decode($head, true);
        if (!isset($unhead["x-wiaxy-signature"])) {
            http_response_code(401);
            die(json_encode(array("message" => "NO_AUTH_HEADER_FOUND")));
        }
        $app_secret_key = "Bill_Stack-SEC-KEY-44194499328b0a6f7c024074aa0183b6";
        $hash = md5($app_secret_key);
        if ($unhead["x-wiaxy-signature"] === $hash) {
            if ($res) {
                $amount_paid = $res["data"]["amount"];
                $payment_ref = $res["data"]["transaction_ref"];
                $payment_status = "PAID";
                $paidon = date("h:i:sa d m Y");
                $payment_ref = $res["data"]["wiaxy_ref"];
                $customer_name = $res["data"]["customer"]["firstName"] . " " . $res["data"]["customer"]["lastName"];
                $cEmail = $res["data"]["customer"]["email"];
                $trans_status = $res["event"];
                // check if $payment_ref is empty
                if (empty($payment_ref)) {
                    return response()->json(['message' => 'payment ref is empty', 'status' => false], 404);
                }

                if ($trans_status != 'PAYMENT_NOTIFICATION') {
                    return;
                }
                $user = User::where(['email' => $cEmail, 'status' => 1])->first();
                if (!$user) {
                    return response()->json('Unable to find user', 403);
                }
                $transid = $this->purchase_ref('AUTOMATED_');
                $charges = 50;
                $credit = $amount_paid - $charges;
                DB::beginTransaction();
                $user = User::where('id', $user->id)->lockForUpdate()->first();
                // Double-check reference inside transaction
                if (Deposit::where('monify_ref', $payment_ref)->exists()) {
                    DB::rollBack();
                    return response()->json('Transaction Ref Exists', 200);
                }
                // Insert deposit
                Deposit::create([
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'amount' => $amount_paid,
                    'oldbal' => $user->bal,
                    'newbal' => $user->bal + $credit,
                    'wallet_type' => 'User Wallet',
                    'type' => 'Automated Bank Transfer',
                    'credit_by' => 'BillStack Automated Bank Transfer',
                    'date' => now(),
                    'status' => 1,
                    'transid' => $transid,
                    'charges' => $charges,
                    'monify_ref' => $payment_ref
                ]);
                // Update user balance
                $user->bal += $credit;
                $user->save();
                // Notification
                Notif::create([
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'message' => 'Account Credited By Automated Bank Transfer ₦' . number_format($credit, 2),
                    'date' => now(),
                    'title' => 'Account credited',
                    'adex' => 0
                ]);
                // History
                History::create([
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'amount' => $credit,
                    'message' => 'Account Credited By Automated Bank Transfer ₦' . number_format($credit, 2),
                    'oldbal' => $user->bal - $credit,
                    'newbal' => $user->bal,
                    'adex_date' => $this->system_date(),
                    'plan_status' => 1,
                    'transid' => $transid,
                    'my_trans_id' => $transid,
                    'role' => 'credit'
                ]);
                // Referral
                $this->handleReferral($user, $credit);
                DB::commit();
                return response()->json('ok');
            }
        } else {
            return response()->json(["status" => false, "message" => "NOT_FROM_SOURCE"], 400);
        }
    }
    public function BankTransfer(Request $request)
    {
        $user = auth()->user();
        $general = General::first();
        $main_validator = validator::make($request->all(), [
            'account_number' => 'required',
            'bank_name' => 'required',
            'bank_code' => 'required',
            'amount_sent' => 'required|numeric'
        ]);
        if ($main_validator->fails()) {
            return response()->json([
                'message' => $main_validator->errors()->first(),
                'status' => 403
            ], 403);
        } else {
            $send_request = "https://api.monnify.com/api/v1/disbursements/account/validate?accountNumber=$request->account_number&bankCode=$request->bank_code";
            $response = Http::get($send_request);
            if (!$response->successful()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Inavlid Account Details'
                ], 403);
            }
            $json_response = $response->json();
            if (!empty($json_response)) {
                if ($json_response['requestSuccessful'] == true) {
                    $transid = $this->purchase_ref('Bank_');
                    $data_bank = [
                        'account_number' => $request->account_number,
                        'bank_name' => $request->bank_name,
                        'bank_code' => $request->bank_code,
                        'account_name' => $json_response['responseBody']['accountName'],
                        'amount' => $request->amount_sent,
                        'date' =>  now(),
                        'plan_status' => 0,
                        'username' => $user->username,
                        'user_id' => $user->id,
                        'transid' => $transid
                    ];
                    BankTransfer::create($data_bank);
                    $admins = DB::table('user')->where(['status' => 1])->where(function ($query) {
                        $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                    })->get();
                    foreach ($admins as $admin) {
                        $email_data = [
                            'email' => $admin->email,
                            'username' => $user->username,
                            'title' => 'Manual Bank Transfer',
                            'sender_mail' => $general->app_email,
                            'app_name' => $general->app_name,
                            'mes' => $user->username . " Transferred  ₦" . number_format($request->amount_sent, 2) . " to your bank account. Reference is => " . $transid
                        ];
                        MailController::send_mail($email_data, 'email.purchase');
                    }
                    ModelsRequest::create(['username' => $user->username, 'user_id' => $user->id, 'message' => $user->username . " Transferred  ₦" . number_format($request->amount_sent, 2) . " to your bank account. Reference is => " . $transid, 'date' => now(), 'transid' => $transid, 'status' => 0, 'title' => 'MANUAL BANK TRANSFER']);
                    return response()->json('success');
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Inavlid Account Details'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Inavlid Account Details'
                ])->setStatusCode(403);
            }
        }
    }
    public function MonnifyWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $eventData = $payload['eventData'] ?? null;
        if (!$eventData) {
            return response()->json('Invalid payload', 400);
        }
        $amount_paid     = $eventData['amountPaid'];
        $payment_ref     = $eventData['transactionReference'] ?? $eventData['paymentReference'];
        $payment_status  = strtolower($eventData['paymentStatus']);
        $customer_name   = $eventData['customer'];
        // Ignore if already processed
        if (Deposit::where(['monify_ref' => $payment_ref, 'status' => 1])->exists()) {
            return response()->json('Transaction already processed', 200);
        }
        if ($payment_status !== 'paid') {
            return response()->json('Payment not successful', 400);
        }
        DB::beginTransaction();
        // Case 1: Deposit already exists but not yet confirmed
        $deposit = Deposit::where('monify_ref', $payment_ref)->whereNotIn('status', [1, 2])->first();
        if ($deposit) {
            $user = User::where(['username' => $deposit->username, 'status' => 1])->lockForUpdate()->first();
            if (!$user) {
                DB::rollBack();
                return response()->json('User not found', 404);
            }
            $credit = $deposit->amount - $deposit->charges;
            // update deposit
            $deposit->update([
                'status' => 1,
                'oldbal' => $user->bal,
                'newbal' => $user->bal + $credit
            ]);
            // update balance
            $user->update(['bal' => $user->bal + $credit]);
            // notif
            Notif::create([
                'user_id'  => $user->id,
                'username' => $user->username,
                'message'  => 'Account Credited By Monnify ATM ₦' . number_format($credit, 2),
                'date'     => now(),
                'title'    => 'Account credited',
                'adex'     => 0
            ]);
            // history
            History::create([
                'user_id'   => $user->id,
                'username'  => $user->username,
                'amount'    => $credit,
                'message'   => 'Account Credited By Monnify ATM ₦' . number_format($credit, 2),
                'oldbal'    => $user->bal,
                'newbal'    => $user->bal + $credit,
                'adex_date' => $this->system_date(),
                'plan_status' => 1,
                'transid'   => $deposit->transid,
                'my_trans_id' => $deposit->transid,
                'role'      => 'credit'
            ]);
            $this->handleReferral($user, $credit);
            DB::commit();
            return response()->json('ok');
        }
        // Case 2: New payment → insert deposit
        $user = User::where('status', 1)
            ->where(function ($q) use ($customer_name) {
                $q->where('username', $customer_name['name'])
                    ->orWhere('email', $customer_name['email']);
            })
            ->lockForUpdate()
            ->first();
        if (!$user) {
            DB::rollBack();
            return response()->json('User not found', 404);
        }
        // charges
        if ($amount_paid > 5000) {
            $charges =  ($amount_paid / 100) * 1.7;
        } else {
            $charges = 50;
        }
        $credit = $amount_paid - $charges;
        $transid = $this->purchase_ref('AUTOMATED_');
        // create deposit
        Deposit::create([
            'user_id'     => $user->id,
            'username'    => $user->username,
            'amount'      => $amount_paid,
            'oldbal'      => $user->bal,
            'newbal'      => $user->bal + $credit,
            'wallet_type' => 'User Wallet',
            'type'        => 'Automated Bank Transfer',
            'credit_by'   => 'Monnify Automated Bank Transfer',
            'date'        => now(),
            'status'      => 1,
            'transid'     => $transid,
            'charges'     => $charges,
            'monify_ref'  => $payment_ref
        ]);

        // update balance
        $user->update(['bal' => $user->bal + $credit]);

        // notif
        Notif::create([
            'user_id'  => $user->id,
            'username' => $user->username,
            'message'  => 'Account Credited By Automated Bank Transfer ₦' . number_format($credit, 2),
            'date'     => now(),
            'title'    => 'Account credited',
            'adex'     => 0
        ]);

        // history
        History::create([
            'user_id'   => $user->id,
            'username'  => $user->username,
            'amount'    => $credit,
            'message'   => 'Account Credited By Automated Bank Transfer ₦' . number_format($credit, 2),
            'oldbal'    => $user->bal,
            'newbal'    => $user->bal + $credit,
            'adex_date' => $this->system_date(),
            'plan_status' => 1,
            'transid'   => $transid,
            'my_trans_id' => $transid,
            'role'      => 'credit'
        ]);

        $this->handleReferral($user, $credit);

        DB::commit();
        return response()->json('ok');
    }
    public function Paystack(Request $request)
    {
        // Define your secret key
        $secret = "";
        // Retrieve the signature from the request headers
        $providedSignature = $request->header('x-paystack-signature');
        // Get the request body as a JSON string
        $requestBody = $request->getContent();
        // Generate the HMAC hash
        $generatedHash = hash_hmac('sha512', $requestBody, $secret);
        // Validate the signature
        if ($generatedHash !== $providedSignature) {
            // Log::error('Invalid signature', ['provided_signature' => $providedSignature, 'generated_hash' => $generatedHash]);
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        }
        // Decode the JSON payload
        $data = json_decode($requestBody, true);
        if (isset($data['data'])) {
            $amountPaid = $data['data']['amount'] / 100;
            $paymentRef = $data['data']['reference'];
            $transStatus = $data['event'];
            $customerEmail = $data['data']['customer']['email'];
            $transid = $this->purchase_ref('AUTOMATED_');
            // Validate payment reference
            if (empty($paymentRef)) {
                return response()->json(['status' => 'error', 'message' => 'Payment reference is empty'], 400);
            }
            // Check if the deposit record exists
            if (Deposit::where(['monify_ref' => $paymentRef, 'status' => 1])->doesntExist()) {
                if ($transStatus === 'charge.success') {
                    // Fetch the user record
                    $user = User::where(['email' => $customerEmail, 'status' => 1])->first();
                    if ($user) {
                        if ($amountPaid > 5000) {
                            $charges = ($amountPaid / 100) * 1.5;
                        } else {
                            $charges = ($amountPaid / 100) * 2;
                        }
                        $credit = $amountPaid - $charges;
                        $transId = $this->purchase_ref('AUTOMATED_');
                        DB::beginTransaction();
                        $user = User::where('id', $user->id)->lockForUpdate()->first();
                        // Insert deposit record
                        Deposit::create([
                            'user_id' => $user->id,
                            'username' => $user->username,
                            'amount' => $amountPaid,
                            'oldbal' => $user->bal,
                            'newbal' => $user->bal + $credit,
                            'wallet_type' => 'User Wallet',
                            'type' => 'Automated Bank Transfer',
                            'credit_by' => 'Paystack Automated Bank Transfer',
                            'date' => $this->system_date(),
                            'status' => 1,
                            'transid' => $transId,
                            'charges' => $charges,
                            'monify_ref' => $paymentRef,
                        ]);
                        // Update user balance
                        $user->bal += $credit;
                        $user->save();
                        // Insert notification
                        Notif::create([
                            'user_id' => $user->id,
                            'username' => $user->username,
                            'message' => 'Account Credited By Automated Bank Transfer ₦' . number_format($credit, 2),
                            'date' => $this->system_date(),
                            'adex' => 0,
                            'title'    => 'Account credited'
                        ]);
                        // Insert message
                        History::create([
                            'user_id' => $user->id,
                            'username' => $user->username,
                            'amount' => $credit,
                            'message' => 'Account Credited By Automated Bank Transfer ₦' . number_format($credit, 2),
                            'oldbal' => $user->bal,
                            'newbal' => $user->bal + $credit,
                            'adex_date' => $this->system_date(),
                            'plan_status' => 1,
                            'transid' => $transId,
                            'my_trans_id' => $transid,
                            'role' => 'credit',
                        ]);
                        // Handle referral rewards
                        $this->handleReferral($user, $credit);
                        DB::commit();
                        return response()->json(['status' => 'success', 'message' => 'Webhook processed successfully'], 200);
                    }
                }
            }
        }
        return response()->json(['status' => 'error', 'message' => 'Invalid request payload'], 400);
    }
    public function SafeWebhook(Request $request)
    {
        $jsonData = json_decode($request->getContent());
        if (empty($jsonData->transaction_reference)) {
            return response()->json(['message' => 'Unable to get transaction reference'], 405);
        }
        $deposit_ref   = $jsonData->transaction_reference;
        $payment_status = $jsonData->event_data->status ?? null;
        $amount_paid    = $jsonData->event_data->data->paid ?? 0;
        $payment_type   = $jsonData->package_id ?? null;
        $account_email  = $jsonData->destination->account_email ?? null;

        // Check if deposit already exists
        if (Deposit::where('monify_ref', $deposit_ref)->exists()) {
            return response()->json(['message' => 'Deposit reference already exists'], 405);
        }

        // Find active user
        $user = User::where('email', $account_email)->where('status', 1)->first();
        if (!$user) {
            return response()->json(['message' => 'User Not Found / Not An Active User'], 405);
        }

        // Decide charges + type
        if ($payment_type == 7) {
            $charges = 40;
            $type = "Safehaven";
        } elseif ($payment_type == 101) {
            $charges = 50;
            $type = "SafeHaven (Dynamic Funding)";
        } else {
            $charges = ($amount_paid / 100) * 1.1;
            $type = "SafeHaven";
        }

        $credit = $amount_paid - $charges;
        DB::beginTransaction();
        // Create deposit
        $deposit = Deposit::create([
            'user_id'     => $user->id,
            'username'    => $user->username,
            'amount'      => $credit,
            'oldbal'      => $user->bal,
            'newbal'      => $user->bal + $credit,
            'wallet_type' => 'User Wallet',
            'type'        => $type,
            'credit_by'   => $type,
            'date'        => $this->system_date(),
            'status'      => 1,
            'transid'     => 'collected_' . $deposit_ref,
            'charges'     => $charges,
            'monify_ref'  => $deposit_ref,
        ]);
        // Update user balance
        $user->update([
            'bal' => $user->bal + $credit,
        ]);
        // Notification
        Notif::create([
            'user_id'  => $user->id,
            'username' => $user->username,
            'message'  => "A deposit of ₦" . number_format($credit, 2) . " has been added to your account.",
            'date'     => $this->system_date(),
            'title' => 'Account credited',
            'adex'     => 0,
        ]);
        // Message
        History::create([
            'user_id'     => $user->id,
            'username'    => $user->username,
            'amount'      => $credit,
            'message'     => "A deposit of ₦" . number_format($credit, 2) . " has been added to your account.",
            'oldbal'      => $user->bal,
            'newbal'      => $user->bal + $credit,
            'adex_date'   => $this->system_date(),
            'plan_status' => 1,
            'transid'     => 'collected_' . $deposit_ref,
            'my_trans_id' => 'collected_' . $deposit_ref,
            'role'        => 'credit',
        ]);
        $this->handleReferral($user, $credit);
        DB::commit();
        return response()->json(['message' => 'funded'], 200);
    }
    protected function core()
    {
        return Setting::first();
    }
    /**
     * Handle referral bonus crediting
     */
    protected function handleReferral(User $user, float $credit)
    {
        $setting = $this->core();
        if ($setting->referral == 1 && $user->ref) {
            $hasFirstDeposit = Deposit::where('username', $user->username)
                ->where('status', 1)
                ->whereNotIn('type', ['Admin Funding'])
                ->count() == 1;

            if ($hasFirstDeposit) {
                $user_ref = User::where(['username' => $user->ref, 'status' => 1])
                    ->lockForUpdate()->first();

                if ($user_ref) {
                    $credit_ref = ($credit / 100) * $setting->referral_price;
                    $oldRefBal = $user_ref->refbal;

                    $user_ref->update(['refbal' => $user_ref->refbal + $credit_ref]);

                    History::create([
                        'user_id'   => $user_ref->id,
                        'username'  => $user_ref->username,
                        'amount'    => $credit_ref,
                        'message'   => 'Referral Earning From ' . ucfirst($user->username),
                        'oldbal'    => $oldRefBal,
                        'newbal'    => $user_ref->refbal,
                        'adex_date' => now(),
                        'plan_status' => 1,
                        'transid'   => $this->purchase_ref('EARNING_'),
                        'my_trans_id' => $this->purchase_ref('EARNING_'),
                        'role'      => 'credit'
                    ]);

                    Notif::create([
                        'user_id'  => $user_ref->id,
                        'username' => $user_ref->username,
                        'message'  => 'Referral Earning From ' . ucfirst($user->username),
                        'date'     => now(),
                        'adex'     => 0,
                        'title' => 'Account credited'
                    ]);
                }
            }
        }
    }
   public function UpdateKYC(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'data.request_type' => ['required', 'in:bvn,nin'],
            'data.number' => [
                'required',
                'digits:11',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('data.request_type') === 'bvn') {
                        if (!preg_match('/^\d{11}$/', $value)) {
                            $fail('The number must be a valid BVN number.');
                        }
                    } elseif ($request->input('data.request_type') === 'nin') {
                        if (!preg_match('/^\d{11}$/', $value)) {
                            $fail('The number must be a valid NIN number.');
                        }
                    }
                },
            ],
            'data.validation_type' => ['required', 'in:dob,phone'],
            'data.validation_input' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    $type = $request->input('data.validation_type');
                    if ($type === 'dob' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                        $fail('The DOB must be in Y-m-d format.');
                    }
                    if ($type === 'phone' && !preg_match('/^\d{11}$/', $value)) {
                        $fail('The phone number must be exactly 11 digits.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'status' => 'fail'
            ], 403);
        }

        $request_type      = $request->data['request_type'];
        $number            = $request->data['number'];
        $validation_type   = $request->data['validation_type'];
        $validation_input  = $request->data['validation_input'];
        $charges = ($request_type === 'bvn') ? 50 : 30;
        $user = auth()->user();
        if ($user->bal < $charges) {
            return response()->json([
                'message' => 'Insufficient Account Balance, Please Kindly Fund Your Wallet And Try Again',
                'status'  => 'fail'
            ], 403);
        }
        DB::beginTransaction();
        $user = User::where('id', $user->id)->lockForUpdate()->first();
        $oldBal = $user->bal;
        $newBal = $oldBal - $charges;
        $user->bal = $newBal;
        $user->save();
        $history = History::create([
            'username'   => $user->username,
            'user_id'    => $user->id,
            'amount'     => $charges,
            'message'    => strtoupper($request_type) . " Validation Request",
            'oldbal'     => $oldBal,
            'newbal'     => $newBal,
            'adex_date'  => now(),
            'plan_status' => 1,
            'transid'    => $this->purchase_ref('validate_' . $request_type . '_'),
            'my_trans_id' => bin2hex(random_bytes(8)),
            'role'       => 'debit'
        ]);
        DB::commit();
        $adexKey = AdexKey::first();
        $response = Http::withHeaders([
            'api-key'       => $adexKey->x_akey,
            'Authorization' => 'Bearer ' . $adexKey->x_skey,
        ])->post('https://api.xixapay.com/api/identity/verify', [
            'businessId' => $adexKey->x_business_id,
            'id_number'  => $number,
            'id_type'    => $request_type,
        ]);
        
        if (!$response->ok()) {
            \Log::error('XixPay identity verify failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'user_id' => $user->id,
            ]);
            return response()->json([
                'status'  => 'fail',
                'message' => 'Verification service unavailable, please try again later.'
            ], 500);
        }

        $result = $response->json();
        if (isset($result['status']) && $result['status'] === 'success') {
            $apiData = $result['data'];
            $isValid = true;
            if ($validation_type === 'dob') {
                $apiDobRaw = $apiData['personal_details']['date_of_birth'] ?? null;
                $apiDob    = null;
                if ($apiDobRaw) {
                    try {
                        $apiDob = Carbon::parse($apiDobRaw)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $apiDob = $apiDobRaw;
                    }
                }
                $inputDob = null;
                try {
                    $inputDob = Carbon::parse($validation_input)->format('Y-m-d');
                } catch (\Exception $e) {
                    $inputDob = $validation_input;
                }
                if ($apiDob !== $inputDob) {
                    $isValid = false;
                }
            } elseif ($validation_type === 'phone') {
                $apiPhone = $apiData['contact_details']['phone_number'] ?? $apiData['contact_details']['phone_number2'] ?? null;
                if ($apiPhone !== $validation_input) {
                    $isValid = false;
                }
            }

            if (!$isValid) {
                return response()->json([
                    'status'  => 'fail',
                    'message' => 'Validation input does not match the identity record.'
                ], 400);
            }
            $profileImagePath = null;
            if (!empty($apiData['biometric_data']['photo'])) {
                try {
                    $photoBase64 = $apiData['biometric_data']['photo'];
                    $photoData   = base64_decode($photoBase64);

                    if ($photoData !== false) {
                        $fileName = 'kyc_' . $user->id . '_' . time() . '.jpg';
                        $filePath = 'kyc_profiles/' . $fileName;
                        \Storage::disk('public')->put($filePath, $photoData);
                        $profileImagePath = 'storage/' . $filePath;
                    }
                } catch (\Exception $e) {
                    $profileImagePath = null;
                }
            }
            // ✅ Save verified KYC profile
            KYCProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'user_email'         => $user->email,
                    'profile_image'      => $profileImagePath,
                    'request_type'       => $request_type,
                    'number'             => $number,
                    'phone_number'       => $apiData['contact_details']['phone_number'] ?? null,
                    'dob'                => $apiData['personal_details']['date_of_birth'] ?? null,
                    'gender'             => $apiData['personal_details']['gender'] ?? null,
                    'state_of_residence' => $apiData['personal_details']['state_of_residence'] ?? null,
                    'nationality'        => $apiData['personal_details']['nationality'] ?? null,
                    'first_name'         => $apiData['personal_details']['first_name'] ?? null,
                    'last_name'          => $apiData['personal_details']['last_name'] ?? null,
                    'middle_name'        => $apiData['personal_details']['middle_name'] ?? null,
                    'ServerResponse'     => $response->body()
                ]
            );
            $user->bvn = $number;
            $user->kyc = 1;
            $user->save();
            return response()->json(['status' => 'success', 'message' => 'Account verified'], 200);
        }
        return response()->json([
            'status'  => 'fail',
            'message' => $result['message'] ?? 'Verification failed'
        ], 400);
    }

    public function DynamicAccount(Request $request)
    {
        $adexKey = AdexKey::first();
        $validator = Validator::make($request->all(), [
            'data.amount' => 'required|numeric|min:' . $adexKey->min . '|max:' . $adexKey->max,
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'status' => 'fail'
            ])->setStatusCode(403);
        }
        $check = auth()->user();
        try {
            $create_dynamic = Http::withHeaders([
                'Authorization' => 'Bearer ' . $adexKey->x_skey,
                'api-key' => $adexKey->x_akey,
                'Content-Type' => 'application/json',
            ])->post('https://api.xixapay.com/api/v1/createVirtualAccount', [
                "name" =>  $check->username,
                "email" => $check->email,
                "bankCode" => ["29007"],
                "phoneNumber" => $check->phone,
                "businessId" => $adexKey->x_business_id,
                "accountType" => "dynamic",
                "amount" => $request->data['amount']
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'fail', 'message' => 'Unable to gnenerate dynamic account number. kindly try again'], 403);
        }
        if ($create_dynamic->successful()) {
            $jsonData = $create_dynamic->json();
            if (empty($jsonData['bankAccounts'])) {
                return response()->json(['status' => 'fail', 'message' => 'Unable to gnenerate dynamic account number. kindly try again'], 403);
            }
            $bankName = $jsonData['bankAccounts'][0]['bankName'];
            $accountName = $jsonData['bankAccounts'][0]['accountName'];
            $accountNumber = $jsonData['bankAccounts'][0]['accountNumber'];
            $expirationTime = $jsonData['bankAccounts'][0]['expire_at'];
            return response()->json(['status' => 'success', 'message' => 'dynamic account created', 'data' => ['account_number' => $accountNumber, 'account_name' => $accountName, 'bank_name' =>  $bankName, 'expire_at' => $expirationTime, 'charges' => $this->getXixapayChargeText(), 'amount' => $request->data['amount']]], 200);
        }
        return response()->json(['status' => 'fail', 'message' => 'Unable to generate Dynamic Account Number Please Try Again Later'], 403);
    }
    private function getXixapayChargeText(): string
    {
        $setting = Setting::first();
        $type = strtolower($setting->xixapay_charge_type ?? 'flat');
        $value = (float) $setting->xixapay_charge_value;
        $cap = (float) $setting->xixapay_charge_cap;

        if ($type === 'percentage') {
            if ($cap > 0) {
                return "{$value}% (Max ₦" . number_format($cap) . ")";
            }
            return "{$value}%";
        }
        // Default to flat charge
        return "₦" . number_format($value, 2);
    }
}
