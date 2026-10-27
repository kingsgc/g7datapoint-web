<?php

namespace App\Http\Controllers\APP;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use App\Models\User;
use App\Models\Setting;
use App\Models\General;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Data;
use App\Models\Airtime;
use App\Models\CableLock;
use App\Models\CableCharge;
use App\Models\CableID;
use App\Models\Cable;
use App\Models\Exam;
use App\Models\ResultCharge;
use App\Models\ExamId;
use App\Models\AirtimeDiscount;
use App\Models\DataPlan;
use App\Models\Cash;
use App\Models\DataCard;
use App\Models\RechargeCard;
use App\Models\CashDiscount;
use App\Models\Network;
use App\Models\Deposit;
use App\Models\Bill;
use App\Models\CablePlan;
use App\Models\BillPlan;
use App\Models\BillCharge;
use App\Services\TokenService;
use App\Services\WalletService;
use App\Models\AdexKey;
use App\Models\History;
use App\Models\Notif;
use App\Models\DataCardPlan;
use App\Models\RechargeCardPlan;
use App\Models\Withdraw;
use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Auth as AuthLogin;

class Auth extends Controller
{
    protected $tokenService;
    protected $walletService;
    protected $AuthControler;

    public function __construct(TokenService $tokenService, WalletService $walletService, AuthController $AuthControler)
    {
        $this->tokenService = $tokenService;
        $this->walletService = $walletService;
        $this->AuthControler = $AuthControler;
    }
    public function AppLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required',
            'app_token' => 'nullable|string',
        ], [
            'username.required' => 'Your Username or Phone Number or Email is Required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 403,
                'message' => $validator->errors()->first()
            ])->setStatusCode(403);
        }
        $user = User::where(function($query) use ($request){
            $query->orWhere('username', $request->username)->orWhere('phone', $request->username)->orWhere('email', $request->username);
        })->first();
        if ($user) {
            $mdpass = md5($request->password);
            $passwordValid =
                password_verify($request->password, $user->password) ||
                $request->password === $user->password ||
                substr(sha1(md5($request->password)), 3, 10) === $user->password ||
                $mdpass === $user->password;
            if (!$passwordValid) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Invalid Password. Note: Password is case sensitive.'
                ], 403);
            }
            $use   = Setting::first();
            switch ((int) $user->status) {
                case 1:
                    $this->walletService->generateAccount($user);
                    $this->walletService->insertStock($user);
                    // if($use && $use->verify_login){
                    // return response()->json([
                    //     'status' => 'verify_login',
                    //     'message' => 'Login successful',
                    //     'user' => $this->generateDetails($user),
                    // ]);
                    // }
                    AuthLogin::login($user);
                    if ($request->filled('app_token')) {
                        $user->update(['app_token' => $request->app_token]);
                    }
                    $general = General::first();
                    $email_data = [
                        'name' => $user->name,
                        'email' => $user->email,
                        'username' => $user->username,
                        'title' => 'WELCOME EMAIL',
                        'sender_mail' => $general->app_email,
                        'system_email' => $general->app_email,
                        'app_name' => $general->app_name,
                        'pin' => $user->pin,
                        'ip_address' => $request->ip(),
                        'logo' => asset('storage/' . $general->app_logo)
                    ];
                    MailController::send_mail($email_data, 'email.login', true);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Login successful',
                        'user' => $this->AuthControler->generateDetails($user),
                        'token' => $this->tokenService->generateAppToken($user->id)
                    ]);
                case 2:
                    return response()->json([
                        'status' => 403,
                        'message' => "{$user->username}, your account has been banned."
                    ], 403);

                case 3:
                    return response()->json([
                        'status' => 403,
                        'message' => "{$user->username}, your account has been deactivated."
                    ], 403);

                case 0:
                    return response()->json([
                        'status' => 'unverify',
                        'user' => $this->AuthControler->generateDetails($user),
                        'token' => $this->tokenService->generateAppToken($user->id),
                        'message' => "{$user->username}, your account is not yet verified."
                    ]);

                default:
                    return response()->json([
                        'status' => 403,
                        'message' => 'System is unable to verify user status.'
                    ], 403);
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Invalid Username and Password'
            ])->setStatusCode(403);
        }
    }
 public function AppVerify(Request $request)
    {
        $user = User::find(auth()->id());
        $general = General::first();
        if ($user->otp == $request->otp) {
            if($user->status == 0){
            $email_data = [
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'title' => 'WELCOME EMAIL',
                'sender_mail' => $general->app_email,
                'system_email' => $general->app_email,
                'app_name' => $general->app_name,
                'pin' => $user->pin,
            ];
            MailController::send_mail($email_data, 'email.welcome');
        }
            $user->update(['otp' => null, 'status' => 1, 'otp_verified_at' => now()]);
            return response()->json([
                'status' => 'success',
                'message' => 'account verify'
            ]);
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Invalid OTP'
            ])->setStatusCode(403);
        }
    }
    public function ResendOtp(Request $request)
    {
        $user  = User::find(auth()->id());
        $general = General::first();
        $otp = random_int(1000, 9999);
        $user->update(['otp' => $otp]);
        $email_data = [
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'title' => 'Account Verification',
            'sender_mail' => $general->app_email,
            'app_name' => env('APP_NAME'),
            'otp' => $otp
        ];
        MailController::send_mail($email_data, 'email.verify');
        return response()->json([
            'status' => 'status',
            'message' => 'New OTP Resent to Your Email'
        ]);
    }
    public function Signup(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|min:8|max:199',
            'email'     => 'required|email|max:255|unique:user,email',
            'phone'     => 'required|numeric|digits:11|unique:user,phone',
            'password'  => 'required|string|min:8',
            'username'  => 'required|string|alpha_num|max:12|unique:user,username',
            'pin'       => 'nullable|numeric|digits:4',
            'ref'       => 'nullable|string|exists:user,username'
        ], [
            'name.required'     => 'Full Name is required',
            'name.min'          => 'Invalid Full Name',
            'name.max'          => 'Invalid Full Name',

            'email.required'    => 'E-mail is required',
            'email.unique'      => 'Email already taken',
            'email.email'       => 'Invalid email address',

            'phone.required'    => 'Phone Number is required',
            'phone.numeric'     => 'Phone Number must be numeric',
            'phone.digits'      => 'Phone Number must be 11 digits',
            'phone.unique'      => 'Phone Number already taken',

            'password.required' => 'Password is required',
            'password.min'      => 'Password must be at least 8 characters',

            'username.required' => 'Username is required',
            'username.unique'   => 'Username already taken',
            'username.max'      => 'Username maximum length is 12 characters',

            'pin.required'      => 'Transaction Pin is required',
            'pin.numeric'       => 'Transaction Pin must be numeric',
            'pin.digits'        => 'Transaction Pin must be exactly 4 digits',

            'ref.exists'        => 'Invalid Referral Username, leave blank if not available'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'status'  => 403
            ], 403);
        }

        // Extra validation: Phone must start with 0
        if (substr($request->phone, 0, 1) !== '0') {
            return response()->json([
                'message' => 'Invalid Phone Number',
                'status'  => 403
            ], 403);
        }
        // Fetch default settings
        $adexKey  = AdexKey::first();
        $settings = Setting::first();
        $general  = General::first();
        try {
            // Create new user
            $user = new User();
            $user->name       = $request->name;
            $user->username   = $request->username;
            $user->email      = $request->email;
            $user->phone      = $request->phone;
            $user->password   = password_hash($request->password, PASSWORD_DEFAULT, ['cost' => 16]);
            $user->apikey     = $this->AuthControler->generateAPIKEY();
            $user->bal        = 0.00;
            $user->refbal     = 0.00;
            $user->ref        = $request->ref;
            $user->type       = 'SMART';
            $user->date       = now();
            $user->kyc        = 0;
            $user->status     = 0;
            $user->user_limit = $adexKey->default_limit ?? 5000;
            $user->pin        = $request->pin;
            $user->app_token = $request->app_token ?? null;
            $user->save();

            // Create wallet + stock
            $this->walletService->generateAccount($user);
            $this->walletService->insertStock($user);

            if ($settings && $settings->is_verify_email) {
                // Require email verification
                $otp = random_int(1024, 9999);
                $user->update(['otp' => $otp]);
                $emailData = [
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'username'   => $user->username,
                    'pin'        => $user->pin,
                    'title'      => 'Account Verification',
                    'sender_mail' => $general->app_email,
                    'app_name'   => env('APP_NAME'),
                    'otp'        => $otp,
                ];
                MailController::send_mail($emailData, 'email.verify');
                return response()->json([
                    'status'   => 'unverify',
                    'username' => $user->username,
                    'user'     => $this->AuthControler->generateDetails($user),
                    'token'    => $this->tokenService->generateAppToken($user->id),
                ]);
            } else {
                // Auto-verify
                $user->update(['status' => 1]);
                $emailData = [
                    'name'        => $user->name,
                    'email'       => $user->email,
                    'username'    => $user->username,
                    'pin'         => $user->pin,
                    'title'       => 'Welcome Email',
                    'sender_mail' => $general->app_email,
                    'system_email' => $general->app_email,
                    'app_name'    => $general->app_name,
                ];
                MailController::send_mail($emailData, 'email.welcome');
                return response()->json([
                    'status'   => 'success',
                    'username' => $user->username,
                    'user'     => $this->AuthControler->generateDetails($user),
                    'token'    => $this->tokenService->generateAppToken($user->id),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Unable to register user, please try again later',
            ], 500);
        }
    }
    public function FingerPrint(Request $request)
    {
        $user = auth()->user();
        $this->walletService->generateAccount($user);
        return response()->json([
            'status' => 'success',
            'user' => $this->AuthControler->generateDetails($user),
            'token' => $user->app_key,
        ]);
    }
    public function APPLOAD(Request $request)
    {
        $user = auth()->user();
        $this->walletService->generateAccount($user);
        $data_purchase = Data::where(['user_id' => $user->id, 'plan_status' => 1])->whereDate('plan_date',  now())->get();
        $total_gb = 0;
        $gb = 0;
        $calculate_gb = '0GB';
        foreach ($data_purchase as $data) {
            $plans = $data->plan_name;
            $check_gb = substr($plans, -2);
            if ($check_gb  == 'MB') {
                $mb = rtrim($plans, "MB");
                $gb = $mb / 1004;
            } elseif ($check_gb == 'GB') {
                $gb = rtrim($plans, "GB");
            } elseif ($check_gb == 'TB') {
                $tb = rtrim($plans, 'TB');
                $gb = ceil($tb * 1000);
            }
            $total_gb += $gb;
        }
        if ($total_gb >= 100) {
            $calculate_gb = $total_gb / 1000 . 'TB';
        } else {
            $calculate_gb =  $total_gb . 'GB';
        }
        return response()->json([
            'status' => 'success',
            'referral_count' => User::where(['ref' => $user->username])->count(),
            'user' =>  $this->AuthControler->generateDetails($user),
            'data_purchased' => $calculate_gb,
            'setting' => Setting::first(),
            'notif' => Notif::where(['user_id' => $user->id, 'adex' => 0])->count()
        ]);
    }
    public function ProfileImage(Request $request)
    {
        $user = User::find(auth()->id());
        if ($request->has('image')) {
            $image = $request->image;
            $user->update(['profile_image' => $image]);
            $user->refresh();
            return response()->json([
                'status' =>  'success',
                'user' => $this->AuthControler->generateDetails($user)
            ]);
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'Image File Empty'
            ], 403);
        }
    }
    public function AppGeneral(Request $request)
    {
        $setting = Setting::first();
        if ($setting) {
            $setting->notif_message = strip_tags($setting->notif_message);
        }
        return response()->json([
            'general' => General::first(),
            'setting' => $setting,
            'adex_key' => AdexKey::first()->select('bank_name', 'account_number', 'account_name', 'withdrawal_charges')->first(),
        ]);
    }
    public function Network(Request $request)
    {
        $user = auth()->user();
        $typeMap = ['SMART', 'AGENT', 'AWUF', 'API'];
        $user_type = in_array($user->type, $typeMap) ? strtolower($user->type) : 'special';
        if ($request->type == 'data') {
            return response()->json([
                'data' => Network::where(function ($query) {
                    $query->orWhere('network_cg', 1)->orWhere('network_sme', 1)->orWhere('network_g',  1);
                })->select('id', 'network', 'plan_id', 'network_cg', 'network_sme', 'network_g')->get()
            ]);
        } else if ($request->type == 'airtime') {
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
                $net->amount   = $discountRow->{$colBase . '_vtu_' . $user_type} ?? '0';
                $net->share_discount = $discountRow->{$colBase . '_share_' . $user_type} ?? '0';
                return $net;
            });
            return response()->json([
                'data' => $networks
            ]);
        } else if ($request->type == 'cash') {
            $discount = CashDiscount::first();
            $networks = Network::select('network', 'cash', 'plan_id')->where(function ($query) {
                $query->where('cash', 1);
            })->get();
            $networks->transform(function ($net) use ($discount) {
                $colBase = strtolower($net->network) === '9mobile' ? 'mobile' : strtolower($net->network);
                $net->amount = $discount->{$colBase};
                return $net;
            });
            return response()->json([
                'data' => $networks,
            ]);
        }else if($request->type == 'data_card'){
             return response()->json([
                'data' => Network::where('data_card', 1)->select('id', 'network', 'plan_id')->get()
            ]);
        }else if($request->type == 'recharge_card'){
             return response()->json([
                'data' => Network::where('recharge_card', 1)->select('id', 'network', 'plan_id')->get()
            ]);
            
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'invalid type'
            ])->setStatusCode(403);
        }
    }

    public function NetworkType(Request $request)
    {
        if (!empty($request->id)) {
            $net = Network::where(['plan_id' => $request->id]);
            if ($net->count() != 0) {
                $network = $net->first();
                if ($request->type == 'data') {
                    $data_plan = [];
                    if ($network->network_sme == 1) {
                        $data_plan[] = ['network' => 'SME', 'plan_id' => '1', 'id' => 1];
                    }
                    if ($network->network_g == 1) {
                        $data_plan[] = ['network' => 'GIFTING', 'plan_id' => '2', 'id' => 2];
                    }
                    if ($network->network_cg == 1) {
                        $data_plan[] = ['network' => 'COOPERATE GIFTING', 'plan_id' => '3', 'id' => 3];
                    }
                    return response()->json([
                        'data' => $data_plan
                    ]);
                } else if ($request->type == 'airtime') {
                    $user = auth()->user();
                    if ($user->type == 'SMART') {
                        $user_type = strtolower($user->type);
                    } else if ($user->type == 'AGENT') {
                        $user_type = strtolower($user->type);
                    } else if ($user->type == 'AWUF') {
                        $user_type = strtolower($user->type);
                    } else if ($user->type == 'API') {
                        $user_type = strtolower($user->type);
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
                    $vtu_price = $airtime_discount->$check_for_vtu;
                    $share_price = $airtime_discount->$check_for_sns;
                } else {
                    $vtu_price = 0;
                    $share_price = 0;
                }
                $airtime_plan = [];
                if ($network->network_vtu == 1) {
                    $airtime_plan[] = ['network' => 'VTU', 'plan_id' => 'vtu', 'id' => 1, 'amount' => $vtu_price];
                }
                if ($network->network_share == 1) {
                    $airtime_plan[] = ['network' => 'SHARE AND SELL', 'plan_id' => 'sns', 'id' => 2, 'amount' => $share_price];
                }
                return response()->json([
                    'data' => $airtime_plan
                ]);
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'invalid type'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'message' => 'Select Network'
            ])->setStatusCode(403);
        }
    }
    public function DataPlans(Request $request)
    {
        $adex = auth()->user();
        // validate form
        $main_validator = validator::make($request->all(), [
            'network' => 'required',
            'network_type' => 'nullable|string',
        ]);
        // validate user type
        if ($adex->type == 'SMART') {
            $user_type = 'smart';
        } else if ($adex->type == 'AGENT') {
            $user_type = 'agent';
        } else if ($adex->type == 'AWUF') {
            $user_type = 'awuf';
        } else if ($adex->type == 'API') {
            $user_type = 'api';
        } else {
            $user_type = 'special';
        }
        if ($main_validator->fails()) {
            return response()->json([
                'message' => $main_validator->errors()->first(),
                'status' => 403
            ])->setStatusCode(403);
        } else {
            $get_network = Network::where('plan_id', $request->network)->first();
            if ($get_network) {
                if (isset($request->network_type)) {
                    $all_plan = DataPlan::where(['network' => $get_network->network, 'plan_type' => $request->network_type, 'plan_status' => 1]);
                    $data_plan = [];
                    foreach ($all_plan->get() as $adex => $plan) {
                        $data_plan[] =  ['name' => $plan->plan_name . $plan->plan_size . ' ' . $plan->plan_type . ' = ₦' . number_format($plan->$user_type, 2) . ' ' . $plan->plan_day, 'plan_id' => $plan->plan_id, 'amount' => '₦' . number_format($plan->$user_type, 2), 'id' => $plan->id];
                    }
                    return response()->json([
                        'status' => 'success',
                        'data' => $data_plan,
                        'network' => $get_network->network,
                        'plan_type' => $request->network_type
                    ]);
                } else {
                    $sme = [];
                    $cg = [];
                    $gifting = [];
                    foreach (DataPlan::where(['network' => $get_network->network, 'plan_type' => 'SME', 'plan_status' => 1])->get() as $adex => $plan) {
                        $sme[] = ['name' => $plan->plan_name . $plan->plan_size, 'network' => $plan->network, 'plan_type' => $plan->plan_type, 'plan_day' => $plan->plan_day, 'plan_id' => $plan->plan_id, 'amount' => '₦' . number_format($plan->$user_type, 2), 'id' => $plan->id];
                    }
                    foreach (DataPlan::where(['network' => $get_network->network, 'plan_type' => 'GIFTING', 'plan_status' => 1])->get() as $adex => $plan) {
                        $gifting[] = ['name' => $plan->plan_name . $plan->plan_size, 'network' => $plan->network, 'plan_type' => $plan->plan_type, 'plan_day' => $plan->plan_day, 'plan_id' => $plan->plan_id, 'amount' => '₦' . number_format($plan->$user_type, 2), 'id' => $plan->id];
                    }
                    foreach (DataPlan::where(['network' => $get_network->network, 'plan_type' => 'COOPERATE GIFTING', 'plan_status' => 1])->get() as $adex => $plan) {
                        $cg[] = ['name' => $plan->plan_name . $plan->plan_size, 'plan_type' => $plan->plan_type, 'network' => $plan->network, 'plan_day' => $plan->plan_day, 'plan_id' => $plan->plan_id, 'amount' => '₦' . number_format($plan->$user_type, 2), 'id' => $plan->id];
                    }
                    return response()->json([
                        'data' => [
                            'sme' => $sme,
                            'cg' => $cg,
                            'gifting' => $gifting
                        ]
                    ]);
                }
            } else {
                return response()->json([
                    'message' => 'please select network'
                ])->setStatusCode(403);
            }
        }
    }

    public function TransactionPin(Request $request)
    {
        $main_validator = validator::make($request->all(), [
            'pin' => 'required|numeric|digits:4',
        ]);
        if ($main_validator->fails()) {
            return response()->json([
                'message' => $main_validator->errors()->first(),
                'status' => 403
            ])->setStatusCode(403);
        } else {
            $user = auth()->user();
            if ($user->pin == $request->pin) {
                return response()->json([
                    'message' => 'correct',
                    'status' => 'success'
                ]);
            } else {
                return response()->json([
                    'message' => 'Invalid Transaction Pin',
                ])->setStatusCode(403);
            }
        }
    }
    public function CableBillID(Request $request)
    {
        switch ($request->type) {
            case 'cable':
                $cablePlans = [];
                $cableLock = CableLock::first();
                $cablePrice = CableCharge::first();
                $cableIds   = CableID::all();

                foreach ($cableIds as $id) {
                    $name = strtoupper($id->cable_name);
                    $isEnabled = match ($name) {
                        'DSTV'     => $cableLock->dstv ?? 0,
                        'GOTV'     => $cableLock->gotv ?? 0,
                        'STARTIME' => $cableLock->startime ?? 0,
                        default    => 0,
                    };

                    $amount = match ($name) {
                        'DSTV'     => $cablePrice->dstv ?? 0,
                        'GOTV'     => $cablePrice->gotv ?? 0,
                        'STARTIME' => $cablePrice->startime ?? 0,
                        default    => 0,
                    };

                    if ($isEnabled) {
                        $cablePlans[] = [
                            'network' => $name,
                            'plan_id' => $id->plan_id,
                            'id'      => $id->id,
                            'amount'  => $amount,
                            'number'  => $cablePrice->direct ?? null,
                        ];
                    }
                }

                return response()->json([
                    'status' => 'success',
                    'data'   => $cablePlans,
                ]);

            case 'bill':
                $billPlans = [];
                $billIds   = BillPlan::where('plan_status', 1)->get();
                $billPrice = BillCharge::first();

                foreach ($billIds as $id) {
                    $billPlans[] = [
                        'network' => $id->disco_name,
                        'plan_id' => $id->plan_id,
                        'id'      => $id->id,
                        'logo' => asset('storage/' . $id->logo),
                        'amount'  => ($billPrice && $billPrice->direct == 1) ? $id->commission : 0,
                    ];
                }

                return response()->json([
                    'status' => 'success',
                    'data'   => $billPlans,
                ]);

            case 'exam':
                $examPlans = [];
                $examIds   = ExamId::all();
                $examLock  = CableLock::first();
                $examPrice = ResultCharge::first();

                foreach ($examIds as $id) {
                    $name = strtoupper($id->exam_name);
                    $isEnabled = match ($name) {
                        'WAEC'   => $examLock->waec ?? 0,
                        'NECO'   => $examLock->neco ?? 0,
                        'NABTEB' => $examLock->nabteb ?? 0,
                        default  => 0,
                    };

                    $amount = match ($name) {
                        'WAEC'   => $examPrice->waec ?? 0,
                        'NECO'   => $examPrice->neco ?? 0,
                        'NABTEB' => $examPrice->nabteb ?? 0,
                        default  => 0,
                    };

                    if ($isEnabled) {
                        $examPlans[] = [
                            'network' => $name,
                            'plan_id' => $id->plan_id,
                            'id'      => $id->id,
                            'amount'  => $amount,
                        ];
                    }
                }

                return response()->json([
                    'status' => 'success',
                    'data'   => $examPlans,
                ]);

            default:
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Not Available',
                ], 403);
        }
    }

    public function CablePlan(Request $request)
    {
        if (CableID::where('plan_id', $request->cable)->count() == 1) {
            $cable_plan = [];
            $cable_id = CableID::where('plan_id', $request->cable)->first();
            $cable_get = CablePlan::where(['plan_status' => 1, 'cable_name' => $cable_id->cable_name])->get();
            foreach ($cable_get as $plan) {
                $cable_plan[] = ['id' => $plan->id, 'name' => $plan->plan_name . ' ' . '₦' . number_format($plan->plan_price, 2), 'amount' => $plan->plan_price, 'plan_id' => $plan->plan_id];
            }
            return response()->json(
                [
                    'data' => $cable_plan
                ]
            );
        } else {
            return response()->json([
                'message' => 'Cable Required'
            ])->setStatusCode(403);
        }
    }
    public function PriceList(Request $request)
    {
        $user = auth()->user();
        $typeMap = [
            'SMART'  => 'smart',
            'AGENT'  => 'agent',
            'AWUF'   => 'awuf',
            'API'    => 'api',
            'SPECIAL' => 'special',
        ];
        $user_type = $typeMap[$user->type] ?? 'special';
        $mtn_vtu     = "mtn_vtu_{$user_type}";
        $airtel_vtu  = "airtel_vtu_{$user_type}";
        $glo_vtu     = "glo_vtu_{$user_type}";
        $mobile_vtu  = "mobile_vtu_{$user_type}";

        $mtn_share   = "mtn_share_{$user_type}";
        $airtel_share = "airtel_share_{$user_type}";
        $glo_share   = "glo_share_{$user_type}";
        $mobile_share = "mobile_share_{$user_type}";
        $data_plan = [];
        $plans = DataPlan::where('plan_status', 1)->get();
        foreach ($plans as $plan) {
            $data_plan[] = [
                'plan'    => "{$plan->plan_name} {$plan->plan_size} {$plan->plan_type}",
                'network' => $plan->network,
                'price'   => '₦' . number_format($plan->$user_type, 2),
                'id'      => $plan->id,
            ];
        }
        $airtime_plan = [];
        $airtime = AirtimeDiscount::first();
        if ($airtime) {
            $airtime_plan = [
                ['network' => 'MTN VTU',      'percentage' => $airtime->$mtn_vtu],
                ['network' => 'AIRTEL VTU',   'percentage' => $airtime->$airtel_vtu],
                ['network' => 'GLO VTU',      'percentage' => $airtime->$glo_vtu],
                ['network' => '9MOBILE VTU',  'percentage' => $airtime->$mobile_vtu],

                ['network' => 'MTN SNS',      'percentage' => $airtime->$mtn_share],
                ['network' => 'AIRTEL SNS',   'percentage' => $airtime->$airtel_share],
                ['network' => 'GLO SNS',      'percentage' => $airtime->$glo_share],
                ['network' => '9MOBILE SNS',  'percentage' => $airtime->$mobile_share],
            ];
        }
        $cable_plan = [];
        $cables = CablePlan::where('plan_status', 1)->get();
        foreach ($cables as $plan) {
            $cable_plan[] = [
                'cable_name' => $plan->cable_name,
                'plan_name'  => $plan->plan_name,
                'plan_price' => '₦' . number_format($plan->plan_price, 2),
            ];
        }
        $exam_list = [];
        $exam_price = ResultCharge::first();
        $exam_ids   = ExamId::all();
        foreach ($exam_ids as $exam) {
            $amount = match ($exam->exam_name) {
                'WAEC'   => $exam_price->waec ?? 0,
                'NECO'   => $exam_price->neco ?? 0,
                'NABTEB' => $exam_price->nabteb ?? 0,
                default  => null,
            };

            if ($amount !== null) {
                $exam_list[] = [
                    'exam_name' => $exam->exam_name,
                    'amount'    => '₦' . number_format($amount, 2),
                ];
            }
        }
        return response()->json([
            'status'  => 'success',
            'data'    => $data_plan,
            'airtime' => $airtime_plan,
            'cable'   => $cable_plan,
            'exam'    => $exam_list,
        ]);
    }

    public function Transaction(Request $request)
    {
        $user = auth()->user();

        // helper: map status codes
        $mapStatus = fn($code) => match ($code) {
            1 => 'success',
            2 => 'fail',
            0 => 'processing',
            default => 'undefined',
        };

        // --- History Transactions ---
        $trans_history = History::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->take(200)
            ->get()
            ->map(fn($plan) => [
                'transid' => $plan->transid,
                'amount'  => '₦' . number_format($plan->amount, 2),
                'status'  => $mapStatus($plan->plan_status),
                'oldbal'  => '₦' . number_format($plan->oldbal, 2),
                'newbal'  => '₦' . number_format($plan->newbal, 2),
                'date'    => $plan->adex_date,
                'message' => $plan->message,
            ]);

        // --- Data Transactions ---
        $data_trans = Data::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->take(200)
            ->get()
            ->map(fn($plan) => [
                'transid'     => $plan->transid,
                'network'     => $plan->network,
                'plan_name'   => $plan->plan_name,
                'plan_type'   => $plan->network_type,
                'phone'       => $plan->plan_phone,
                'amount'      => '₦' . number_format($plan->amount, 2),
                'status'      => $mapStatus($plan->plan_status),
                'oldbal'      => '₦' . number_format($plan->oldbal, 2),
                'newbal'      => '₦' . number_format($plan->newbal, 2),
                'date'        => $plan->plan_date,
                'api_response' => $plan->api_response,
            ]);

        // --- Airtime Transactions ---
        $airtime_trans = Airtime::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->take(200)
            ->get()
            ->map(fn($plan) => [
                'transid'      => $plan->transid,
                'network'      => $plan->network,
                'network_type' => $plan->network_type,
                'phone'        => $plan->plan_phone,
                'amount'       => '₦' . number_format($plan->amount, 2),
                'status'       => $mapStatus($plan->plan_status),
                'oldbal'       => '₦' . number_format($plan->oldbal, 2),
                'newbal'       => '₦' . number_format($plan->newbal, 2),
                'date'         => $plan->plan_date,
                'discount'     => '₦' . number_format($plan->discount, 2),
            ]);

        // --- Cable Transactions ---
        $cable_trans = Cable::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->take(200)
            ->get()
            ->map(fn($plan) => [
                'transid'       => $plan->transid,
                'cable_name'    => $plan->cable_name,
                'plan_name'     => $plan->cable_plan,
                'iuc'           => $plan->iuc,
                'amount'        => '₦' . number_format($plan->amount, 2),
                'status'        => $mapStatus($plan->plan_status),
                'oldbal'        => '₦' . number_format($plan->oldbal, 2),
                'newbal'        => '₦' . number_format($plan->newbal, 2),
                'date'          => $plan->plan_date,
                'charges'       => '₦' . number_format($plan->charges, 2),
                'customer_name' => $plan->customer_name,
            ]);

        // --- Bill Transactions ---
        $bill_trans = Bill::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->take(200)
            ->get()
            ->map(fn($plan) => [
                'transid'       => $plan->transid,
                'disco'         => $plan->disco_name,
                'meter_type'    => $plan->meter_type,
                'meter_number'  => $plan->meter_number,
                'amount'        => '₦' . number_format($plan->amount, 2),
                'status'        => $mapStatus($plan->plan_status),
                'oldbal'        => '₦' . number_format($plan->oldbal, 2),
                'newbal'        => '₦' . number_format($plan->newbal, 2),
                'date'          => $plan->plan_date,
                'charges'       => '₦' . number_format($plan->charges, 2),
                'customer_name' => $plan->customer_name,
                'purchase_code' => $plan->token,
            ]);

        // --- Exam Transactions ---
        $exam_trans = Exam::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->take(200)
            ->get()
            ->map(fn($plan) => [
                'transid'      => $plan->transid,
                'exam_name'    => $plan->exam_name,
                'quantity'     => $plan->quantity,
                'amount'       => '₦' . number_format($plan->amount, 2),
                'status'       => $mapStatus($plan->plan_status),
                'oldbal'       => '₦' . number_format($plan->oldbal, 2),
                'newbal'       => '₦' . number_format($plan->newbal, 2),
                'date'         => $plan->plan_date,
                'purchase_code' => $plan->purchase_code,
            ]);

        // --- Deposit Transactions ---
        $deposit_trans = Deposit::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->take(200)
            ->get()
            ->map(fn($plan) => [
                'transid'     => $plan->transid,
                'type'        => $plan->type,
                'wallet_type' => $plan->wallet_type,
                'amount'      => '₦' . number_format($plan->amount, 2),
                'status'      => $mapStatus($plan->status),
                'oldbal'      => '₦' . number_format($plan->oldbal, 2),
                'newbal'      => '₦' . number_format($plan->newbal, 2),
                'date'        => $plan->date,
                'charges'     => '₦' . number_format($plan->charges, 2),
            ]);

        return response()->json([
            'status'        => 'success',
            'trans_history' => $trans_history,
            'data'          => $data_trans,
            'airtime'       => $airtime_trans,
            'cable'         => $cable_trans,
            'bill'          => $bill_trans,
            'exam'          => $exam_trans,
            'deposit'       => $deposit_trans,
        ]);
    }

    public function Notification(Request $request)
    {
        $user = auth()->user();
        return response()->json([
            'data' => Notif::where(['user_id' => $user->id])->orderBy('id', 'desc')->paginate(100)
        ]);
    }
    public function CompleteProfile(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'app_key' => 'required|string',
            'transaction_pin' => 'required|digits:4',

        ], [
            'transaction_pin.required' => 'Transaction Pin Required',
            'transaction_pin.digits' => 'Transaction Pin Digit Must Be 4 Digits'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 403,
                'message' => $validator->errors()->first()
            ])->setStatusCode(403);
        } else {
            $user = User::find(auth()->id());
            $user->update(['pin' => $request->transaction_pin]);
            $user->refresh();
            $general = General::first();
            $email_data = [
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'title' => 'WELCOME EMAIL',
                'sender_mail' => $general->app_email,
                'system_email' => $general->app_email,
                'app_name' => $general->app_name,
                'pin' => $user->pin,
            ];
            MailController::send_mail($email_data, 'email.welcome');

            return response()->json([
                'status' =>  'success',
                'user' => $this->AuthControler->generateDetails($user),
            ]);
        }
    }
   public function NewPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_key' => 'required|string',
            'transaction_pin' => 'required|digits:4',
        ], [
            'transaction_pin.required' => 'Transaction Pin Required',
            'transaction_pin.digits' => 'Transaction Pin Digit Must Be 4 Digits'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 403,
                'message' => $validator->errors()->first()
            ])->setStatusCode(403);
        } 
            $user = auth()->user();
           if (!$user->otp_verified_at || $user->otp_verified_at->diffInMinutes(now()) > 5) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Session expired. Please verify OTP again.'
                ], 403);
            }
            if ($user->pin === $request->transaction_pin) {
                return response()->json([
                    'status' => 'fail', 
                    'message' => 'New PIN cannot be the same as your old PIN'
                ], 403);
            }
            $user = User::find(auth()->id());
            $user->update(['pin' => $request->transaction_pin, 'otp' => null, 'otp_verified_at' => null]);
            $user->refresh();
            return response()->json([
                'status' =>  'success',
            ]);
    }
    public function DepositTransaction(Request $request)
    {
        $user = auth()->user();
        $trans = [];
        if ($request->post_number == '10') {
            $trans = History::where(function ($function) {
                $function->orWhere('role', 'debit')->orWhere('role', 'credit');
            })->where('username', $user->username)->orderBy('id', 'desc')->get()->take(10);
        } else {
            $trans = History::where(function ($function) {
                $function->orWhere('role', 'debit')->orWhere('role', 'credit');
            })->where('username', $user->username)->orderBy('id', 'desc')->get()->take(20);
        }
        return response()->json([
            'status' => 'success',
            'trans' => $trans
        ]);
    }
    public function TransactionInvoice(Request $request)
    {
        $user = auth()->user();
        $main_trans = History::where('user_id', $user->id)->where(function ($query) use ($request) {
            $query->where('my_trans_id', $request->transid)->orWhere('transid', $request->transid);
        })->first();
        if (!$main_trans) {
            return response()->json([
                'message' => 'Transaction ID Not Found'
            ], 403);
        }
        $roleModels = [
            'data'          => Data::class,
            'airtime'       => Airtime::class,
            'credit'        => Deposit::class,
            'cash'          => Cash::class,
            'bill'          => Bill::class,
            'cable'         => Cable::class,
            'exam'          => Exam::class,
            'data_card'     => DataCard::class,
            'recharge_card' => RechargeCard::class,
            'withdrawal'      => Withdraw::class
        ];

        $extraData = null;

        if (array_key_exists($main_trans->role, $roleModels)) {
            $model = $roleModels[$main_trans->role];

            // Some roles use `user_id`, some use `username`
            $query = $model::query();
            $query->where('user_id', $user->id);

            // Match by transaction ID
            if ($main_trans->role === 'credit') {
                $query->where('transid', $main_trans->transid);
            } else {
                $query->where('my_trans_id', $main_trans->my_trans_id);
            }

            $extraData = $query->first();

            // Format date fields if available
            if ($extraData && isset($extraData->created_at)) {
                $extraData->formatted_date = \Carbon\Carbon::parse($extraData->created_at)->format('d M Y, h:i A');
            }
        }

        // Also format main_trans date
        if (isset($main_trans->created_at)) {
            $main_trans->formatted_date = \Carbon\Carbon::parse($main_trans->created_at)->format('d M Y, h:i A');
        }
        unset(
            $extraData->vending_system,
            $extraData->vending_url,
            $extraData->request_time,
            $extraData->response_time,
            $extraData->ServerResponse,
            $extraData->user_id
        );
        return response()->json([
            'status' => 'success',
            'trans'  => [
                'main_trans' => $main_trans,
                'data'       => $extraData
            ]
        ]);
    }
    public function TransactionHistoryAdex(Request $request)
    {
        $user = auth()->user();
        $search = strtolower(trim($request->search));

        // Map transaction types to models and searchable fields
        $transactionTypes = [
            'data' => [Data::class, ['network', 'plan_date', 'oldbal', 'newbal', 'transid', 'api_response', 'plan_phone', 'system', 'network_type', 'wallet', 'plan_name', 'username']],
            'airtime' => [Airtime::class, ['network', 'plan_date', 'oldbal', 'newbal', 'transid', 'plan_phone', 'system', 'network_type', 'username']],
            'deposit' => [Deposit::class, ['amount', 'username', 'date', 'oldbal', 'transid', 'newbal', 'wallet_type', 'type', 'credit_by', 'charges', 'monify_ref']],
            'cash' => [Cash::class, ['amount', 'username', 'amount_credit', 'oldbal', 'transid', 'newbal', 'payment_type', 'network', 'sender_number', 'system', 'username']],
            'bill' => [Bill::class, ['disco_name', 'username', 'plan_date', 'oldbal', 'transid', 'newbal', 'meter_number', 'meter_type', 'customer_name', 'token', 'system']],
            'earning' => [History::class, ['adex_date', 'oldbal', 'transid', 'newbal']],
            'cable' => [Cable::class, ['amount', 'username', 'plan_date', 'charges', 'transid', 'cable_plan', 'cable_name', 'iuc', 'customer_name']],
            'exam' => [Exam::class, ['amount', 'username', 'purchase_code', 'oldbal', 'transid', 'newbal', 'plan_date', 'exam_name', 'quantity']],
            'data_card' => [DataCard::class, ['username', 'plan_date', 'load_pin', 'transid', 'newbal', 'system', 'plan_type', 'card_name', 'plan_name']],
            'recharge_card' => [RechargeCard::class, ['username', 'plan_date', 'load_pin', 'transid', 'newbal', 'system', 'card_name', 'plan_name']],
            'withdrawal' => [Withdraw::class, ['user_email', 'bank_name', 'accountName', 'accountNumber', 'amount', 'charges', 'narration', 'plan_date', 'newbal', 'oldbal', 'transid', 'my_trans_id', 'serverResponse']],
            'default' => [History::class, ['adex_date', 'oldbal', 'transid', 'newbal', 'message']],
        ];

        // Pick the right model and fields
        $type = $request->type;
        [$model, $fields] = $transactionTypes[$type] ?? $transactionTypes['default'];

        // Base query
        $query = $model::where('user_id', $user->id);

        // Extra filter for earning
        if ($type === 'earning') {
            $query->where('role', 'earning');
        }
        // Apply search if available
        if (!empty($search)) {
            $query->where(function ($q) use ($fields, $search) {
                foreach ($fields as $field) {
                    $q->orWhere($field, 'LIKE', "%$search%");
                }
            });
        }
        $data = $query->orderBy('id', 'desc')->paginate(25);
        $hiddenFields = ['vending_system', 'vending_url', 'request_time', 'response_time', 'ServerResponse', 'user_id'];
        $data->getCollection()->transform(function ($item) use ($hiddenFields) {
            return $item->makeHidden($hiddenFields);
        });
        return response()->json(['data' => $data]);
    }
    public function DataCardPlans(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'network' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }
        $userTypes = [
            'SMART'  => 'smart',
            'AGENT'  => 'agent',
            'AWUF'   => 'awuf',
            'API'    => 'api',
        ];
        $userType = $userTypes[$user->type] ?? 'special';
        $network = Network::where('plan_id', $request->network)->first();
        if (!$network) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Please select a valid network',
            ], 403);
        }
        $plans = DataCardPlan::where([
            'network'     => $network->network,
            'plan_status' => 1,
        ])->get();
        if ($plans->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'data'   => [],
            ]);
        }
        $dataPlans = $plans->map(function ($plan) use ($userType) {
            return [
                'id'      => $plan->id,
                'plan_id' => $plan->plan_id,
                'name'    => "{$plan->name}{$plan->plan_size} {$plan->plan_type} = ₦" . number_format($plan->$userType, 2) . " {$plan->plan_day}",
                'amount'  => '₦' . number_format($plan->$userType, 2),
            ];
        });
        return response()->json([
            'status' => 'success',
            'data'   => $dataPlans,
        ]);
    }
    public function RechargeCardPlans(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'network' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }
        $userTypes = [
            'SMART' => 'smart',
            'AGENT' => 'agent',
            'AWUF'  => 'awuf',
            'API'   => 'api',
        ];
        $userType = $userTypes[$user->type] ?? 'special';
        $network = Network::where('plan_id', $request->network)->first();
        if (!$network) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Please select a valid network',
            ], 403);
        }
        $plans = RechargeCardPlan::where([
            'network'     => $network->network,
            'plan_status' => 1,
        ])->get();
        if ($plans->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'data'   => [],
            ]);
        }
        $dataPlans = $plans->map(function ($plan) use ($userType) {
            return [
                'id'      => $plan->id,
                'plan_id' => $plan->plan_id,
                'name'    => "{$plan->name} = ₦" . number_format($plan->$userType, 2),
                'amount'  => '₦' . number_format($plan->$userType, 2),
            ];
        });
        return response()->json([
            'status' => 'success',
            'data'   => $dataPlans,
        ]);
    }
    public function notifyAccountDeletionRequest(Request $request)
    {
        $user = auth()->user();
        $general = General::first();
        $admins = User::where('status', 1)
            ->where('type', 'ADMIN')
            ->get();
        foreach ($admins as $admin) {
            $emailData = [
                'name'           => $user->name,
                'phone'          => $user->phone,
                'email'          => $admin->email,   // recipient
                'user_email'     => $user->email,
                'username'       => $user->username,
                'balance'        => number_format($user->bal, 2),
                'title'          => strtoupper($admin->username) . ', ' . strtoupper($user->name) . ' wants to delete their account',
                'sender_mail'    => $general->app_email,
                'app_name'       => env('APP_NAME'),
                'type'           => $user->type,
                'admin_username' => $admin->username,
            ];
            MailController::send_mail($emailData, 'email.delete_account');
        }
        return response()->json([
            'status'  => 'success',
            'message' => 'Account deletion request notification sent to admins.',
        ]);
    }

    public function SendOtp(Request $request)
    {
        $user = User::find(auth()->id());
        $otp = random_int(1000, 9999);
        $user->update(['otp' => $otp]);
        $email_data = [
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'title' => 'Account Verification',
            'pin' => $user->pin,
            'sender_mail' => env('MAIL_FROM_ADDRESS'),
            'app_name' => env('APP_NAME'),
            'otp' => $otp
        ];
        MailController::send_mail($email_data, 'email.reset_pin');
        return response()->json([
            'status' => 'success',
        ]);
    }
    public function AppSystemNotification(Request $request)
    {
        $user = auth()->user();
        if ($user) {
            $paginationLimit = 25;
            $records = Notif::where(['user_id' => $user->id])->orderBy('id', 'desc')->paginate($paginationLimit);
            return response()->json(['data' => $records], 200);
        }
        return response()->json([
            'message' => 'User Not Found',
        ])->setStatusCode(502);
    }
    public function ReadNotification(Request $request)
    {
        $request->validate([
            'notification_id' => 'required'
        ]);
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'User Not Found',
            ], 502);
        }
        Notif::where(['id' => $request->notification_id, 'user_id' => $user->id])->update(['adex' => 1]);
        return response()->json(['status' => 'success', 'message' => 'Notification marked read successfully'], 200);
    }
    public function recentTransaction(Request $request)
    {
        $user = auth()->user();
        return response()->json([
            'status' => 'success',
            'data' => History::where('user_id', $user->id)
                ->orderBy('id', 'desc')
                ->limit(5)
                ->select('role', 'amount', 'transid', 'adex_date', 'plan_status')
                ->get()
        ], 200);
    }
    public function ClearNotification(Request $request)
    {
        $user = auth()->user();
        if ($user) {
            Notif::where(['user_id' => $user->id])->delete();
            return response()->json([
                'status' => 'success'
            ], 200);
        }
    }
    public function carouselData()
    {
        return response()->json([
            'images' => ['https://subwallet.ng/appImage/1_new.png', 'https://subwallet.ng/appImage/2_new.png', 'https://subwallet.ng/appImage/3_new.png', 'https://subwallet.ng/appImage/4_new.png', 'https://subwallet.ng/appImage/5_new.png']
        ], 200);
    }
}
