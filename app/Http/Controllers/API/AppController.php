<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use App\Models\AdexKey;
use App\Models\AirtimeDiscount;
use App\Models\BillCharge;
use App\Models\BillPlan;
use App\Models\CableLock;
use App\Models\CashDiscount;
use App\Models\Feature;
use App\Models\General;
use App\Models\History;
use App\Models\Network;
use App\Models\Notif;
use App\Models\Request as ModelsRequest;
use App\Models\ResultCharge;
use App\Models\Setting;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppController extends Controller
{
    protected $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }
    public function system(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'setting' => Setting::first(),
            'feature' => Feature::get(),
            'general' => General::first(),
            'bank' => AdexKey::select('account_number', 'account_name', 'bank_name', 'min', 'max')->first()
        ]);
    }
    public function buildWebsite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
        ], [
            'url.url' => 'Invalid URL — it must contain (https or www)',
            'url.required' => 'Your website URL is needed for verification',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'status' => 403
            ], 403);
        }
        $user = User::find(auth()->user()->id);
        $setting = Setting::first();
        $general = General::first();
        $date = now();
        if (empty($setting->affliate_price)) {
            return response()->json([
                'status' => 403,
                'message' => 'System is unable to detect affiliate price.'
            ], 403);
        }
        if ($user->bal < $setting->affliate_price) {
            return response()->json([
                'status' => 403,
                'message' => 'Insufficient funds. Your balance is ₦' . number_format($user->bal, 2)
            ], 403);
        }
        $oldBalance = $user->bal;
        $user->bal -= $setting->affliate_price;
        $user->save();
        $newBalance = $user->bal;
        $ref = $this->generate_ref('AFFLIATE_WEBSITE');
        History::create([
            'user_id' => $user->id,
            'username' => $user->username,
            'amount' => $setting->affliate_price,
            'message' => 'Purchase an Affiliate Website',
            'oldbal' => $oldBalance,
            'newbal' => $newBalance,
            'adex_date' => $date,
            'transid' => $ref,
            'plan_status' => 1,
            'role' => 'WEBSITE',
        ]);
        $admins = User::where('status', '1')->whereIn('type', ['ADMIN', 'CUSTOMER'])->get();
        foreach ($admins as $admin) {
            $email_data = [
                'name' => $user->name,
                'email' => $admin->email,
                'username' => $user->username,
                'title' => 'AFFLIATE WEBSITE',
                'sender_mail' => $general->app_email ?? 'noreply@example.com',
                'user_email' => $user->email,
                'app_name' => $general->app_name ?? 'MyApp',
                'website' => $request->url,
                'date' => $date,
                'transid' => $ref,
                'app_phone' => $general->app_phone ?? 'N/A',
            ];
            MailController::send_mail($email_data, 'email.affliate_request');
        }
        ModelsRequest::create([
            'user_id' => $user->id,
            'username' => $user->username,
            'date' => $date,
            'transid' => $ref,
            'status' => 0,
            'title' => 'AFFLIATE WEBSITE',
            'message' => "{$user->username} wants to make an affiliate website. Domain URL is {$request->url} (Account Debited)"
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Your request has been received and will be processed within 3-5 days.',
        ]);
    }
    public function AwufPackage(Request $request)
    {
        return $this->upgradePackage('AWUF', 'awuf_price', 'AWUF_PACKAGE');
    }

    public function AgentPackage(Request $request)
    {
        return $this->upgradePackage('AGENT', 'agent_price', 'AGENT_PACKAGE');
    }

    /**
     * Charge the user the configured price and upgrade their `type` atomically.
     *
     * Bal deduction, History row, and type change all sit inside one DB
     * transaction so a failure in any step (including the FCM dispatch the
     * History mutator triggers) rolls the wallet back. Keeps the user from
     * being silently debited when the upgrade can't be completed.
     */
    private function upgradePackage(string $newType, string $priceField, string $refPrefix)
    {
        $setting = Setting::first();
        $price   = $setting->{$priceField} ?? null;

        if (empty($price)) {
            return response()->json([
                'status'  => 403,
                'message' => 'System is unable to detect price right now',
            ])->setStatusCode(403);
        }

        try {
            $result = DB::transaction(function () use ($newType, $price, $refPrefix) {
                $user = User::where('id', auth()->user()->id)->lockForUpdate()->first();
                if ((float) $user->bal < (float) $price) {
                    return ['ok' => false, 'bal' => (float) $user->bal];
                }

                $oldBal = (float) $user->bal;
                $newBal = $oldBal - (float) $price;
                $ref    = $this->generate_ref($refPrefix);

                $user->bal = $newBal;
                $user->save();

                History::create([
                    'user_id'     => $user->id,
                    'username'    => $user->username,
                    'amount'      => $price,
                    'message'     => "Successfully Upgraded Your Account To {$newType} PACKAGE",
                    'oldbal'      => $oldBal,
                    'newbal'      => $newBal,
                    'adex_date'   => now(),
                    'transid'     => $ref,
                    'role'        => 'UPGRADE',
                    'plan_status' => 1,
                ]);

                $user->update(['type' => $newType]);

                return ['ok' => true, 'newBal' => $newBal];
            });
        } catch (\Throwable $e) {
            Log::error("Package upgrade failed [{$newType}]", [
                'user_id' => auth()->id(),
                'err'     => $e->getMessage(),
            ]);
            return response()->json([
                'status'  => 403,
                'message' => 'Could not complete upgrade. Please try again — your wallet was not debited.',
            ])->setStatusCode(403);
        }

        if (!$result['ok']) {
            return response()->json([
                'status'  => 403,
                'message' => 'Insufficient balance. Fund your wallet and try again ~ ₦' . number_format($result['bal'], 2),
            ])->setStatusCode(403);
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Account upgraded to {$newType} package successfully",
        ]);
    }


    public function SystemNetwork(Request $request)
    {
        $user = auth()->user();
        $typeMap = ['SMART', 'AGENT', 'AWUF', 'API', 'MARKETER'];
        $user_type = $user->type === 'MARKETER' ? 'agent' : (in_array($user->type, $typeMap) ? strtolower($user->type) : 'special');
        if ($request->type == 'data') {
            return response()->json([
                'status' => 'success',
                'network' => Network::select('network', 'plan_id')
                    ->where(function ($query) {
                        $query->orWhere('network_sme', 1)
                            ->orWhere('network_cg', 1)
                            ->orWhere('network_g', 1);
                    })->get()
            ]);
        }
        if ($request->type == 'airtime') {
            $discounts = AirtimeDiscount::first();
            $networks = Network::select(
                'network',
                'network_vtu',
                'network_share',
                'plan_id',
            )->where(function ($query) {
                $query->orWhere('network_vtu', 1)
                    ->orWhere('network_share', 1);
            })->get();
            $networks->transform(function ($net) use ($discounts, $user_type) {
                $colBase = strtolower($net->network) === '9mobile' ? 'mobile' : strtolower($net->network);
                $discountRow = $discounts;
                $net->vtu_discount   = $discountRow->{$colBase . '_vtu_' . $user_type} ?? '0';
                $net->share_discount = $discountRow->{$colBase . '_share_' . $user_type} ?? '0';
                return $net;
            });
            return response()->json([
                'status' => 'success',
                'network' => $networks
            ]);
        }
        if ($request->type == 'cash') {
            $discount = CashDiscount::first();
            $networks = Network::select('network', 'cash', 'plan_id')->where(function ($query) {
                $query->where('cash', 1);
            })->get();
            $networks->transform(function ($net) use ($discount) {
                $colBase = strtolower($net->network) === '9mobile' ? 'mobile' : strtolower($net->network);
                $net->discount = $discount->{$colBase};
                return $net;
            });
            return response()->json([
                'status' => 'success',
                'network' => $networks
            ]);
        }
        return response()->json([
            'status' => 'success',
            'network' => Network::select(
                'network',
                'plan_id',
                'data_card',
                'recharge_card'
            )->get()
        ]);
    }
    public function PlanType(Request $request)
    {
        $network = Network::where('plan_id', $request->id)->first();
        if (!$network) {
            return response()->json(['message' => 'Network Not Found'], 403);
        }
        $planTypes = [];
        if ($network->network_sme == 1) {
            $planTypes[] = 'SME';
        }
        if ($network->network_cg == 1) {
            $planTypes[] = 'COOPERATE GIFTING';
        }
        if ($network->network_g == 1) {
            $planTypes[] = 'GIFTING';
        }
        return response()->json(['planTypes' => $planTypes, 'message' => 'success'], 200);
    }

    public function checkNetworkType(Request $type)
    {
        if (!empty($type->id)) {
            if (isset($type->token)) {
                $network = Network::select('network', 'network_vtu', 'network_share', 'network_sme', 'network_cg', 'network_g', 'plan_id')->where('plan_id', $type->id)->first();
                $user = User::where(['id' => $this->tokenService->verifyToken($type->token), 'status' => 1]);
                if ($user->count() == 1) {
                    $adex = $user->first();
                    if ($adex->type == 'SMART') {
                        $user_type = strtolower($adex->type);
                    } else if ($adex->type == 'AGENT' || $adex->type == 'MARKETER') {
                        $user_type = 'agent';
                    } else if ($adex->type == 'AWUF') {
                        $user_type = strtolower($adex->type);
                    } else if ($adex->type == 'API') {
                        $user_type = strtolower($adex->type);
                    } else {
                        $user_type = 'special';
                    }
                    if ($network->network == '9MOBILE') {
                        $real_network = 'mobile';
                    } else {
                        $real_network = $network->network;
                    }
                    $check_for_vtu = strtolower($real_network) . "_vtu_" . $user_type;
                    $check_for_sns = strtolower($real_network) . "_share_" . $user_type;
                    $airtime_discount = AirtimeDiscount::first();
                    return response()->json([
                        'status' => 'success',
                        'network' => $network,
                        'price_vtu' => $airtime_discount->$check_for_vtu,
                        'price_sns' => $airtime_discount->$check_for_sns
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message'  => 'Reload Your Browser'
                    ])->setStatusCode(403);
                }
            } else {
                $network = Network::select('network', 'network_vtu', 'network_share', 'network_sme', 'network_cg', 'network_g', 'plan_id')->where('plan_id', $type->id)->first();
                return response()->json([
                    'status' => 'success',
                    'network' => $network,
                ]);
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'network plan id need'
            ])->setStatusCode(403);
        }
    }
    public function UserNotif(Request $request)
    {
        $adex = auth()->user();
        $user_request = Notif::where('user_id', $adex->id);
        $profile_image = [];
        foreach ($user_request->orderBy('id', 'desc')->get() as $adex) {
            $profile_image[] = ['username' => $adex->username,   'id' => $adex->id, 'message' => $adex->message, 'date' => $adex->date, 'status' => $adex->adex, 'title' => $adex->title];
        }
        return response()->json([
            'status' => 'success',
            'notif' => $profile_image
        ]);
    }
    public function ClearNotifUser(Request $request)
    {
        $adex = auth()->user();
        Notif::where('user_id', $adex->id)->delete();
        return response()->json('success');
    }

    public function CableName(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'cable' => CableLock::first()
        ]);
    }
    public function BillCal(Request $request)
    {
        $request->validate([
            'disco' => 'required|string'
        ]);
        $billCharge = BillCharge::first();
        $billPlan   = BillPlan::where('plan_id', $request->disco)->first();

        if (!$billPlan) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Bill plan not found'
            ], 404);
        }
        $charges = 0;
        if ($billCharge && $billCharge->direct == 1) {
            $charges = ($request->id / 100) * $billPlan->commission;
        }
        return response()->json([
            'status'  => 'success',
            'charges' => round($charges, 2)
        ]);
    }

    public function DiscoList()
    {
        $billPlans = BillPlan::where('plan_status', 1)->select('plan_id', 'disco_name', 'logo')->get()->map(function ($plan) {
            $plan->logo = asset('storage/' . $plan->logo);
            return $plan;
        });
        return response()->json([
            'status' => 'success',
            'bill' => $billPlans
        ]);
    }
    public function AirtimeCash(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'network' => 'required|string',
        ]);
        $networkInput = strtoupper($request->network);
        $validNetworks = ['MTN', 'AIRTEL', 'GLO', '9MOBILE'];
        if (!in_array($networkInput, $validNetworks)) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Invalid network selected',
            ], 422);
        }
        $networkKey = $networkInput === '9MOBILE' ? 'mobile' : strtolower($networkInput);
        $systemAdmin = CashDiscount::first();
        if (!$systemAdmin || !isset($systemAdmin->$networkKey)) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Cash discount settings missing for selected network',
            ], 500);
        }
        $credit = ($request->amount / 100) * $systemAdmin->$networkKey;
        return response()->json([
            'status' => 'success',
            'amount' => round($credit, 2),
        ]);
    }
    public function BulksmsCal(Request $request)
    {
        return response()->json([
            'amount' => Setting::first()->bulk_sms
        ]);
    }
    public function ResultPrice(Request $request)
    {
        return response()->json([
            'price' => ResultCharge::first()
        ]);
    }
}
