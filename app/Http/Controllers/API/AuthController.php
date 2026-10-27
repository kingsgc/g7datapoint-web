<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use App\Models\User;
use App\Services\TokenService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\General;
use Illuminate\Support\Facades\Hash;
use App\Models\Setting;
use App\Models\AdexKey;
use Illuminate\Support\Str;


class AuthController extends Controller
{

    protected $tokenService;
    protected $walletService;

    public function __construct(TokenService $tokenService, WalletService $walletService)
    {
        $this->tokenService = $tokenService;
        $this->walletService = $walletService;
    }
public function register(Request $request){
    $validator = Validator::make($request->all(), [
        'name'     => ['required','string','max:199','min:8','regex:/^[a-zA-Z]+(?: [a-zA-Z]+)+$/'],
        'email'    => 'required|unique:user,email|max:255|email',
        'phone'    => 'required|numeric|unique:user,phone|digits:11',
        'password' => 'required|min:8',
        'username' => 'required|unique:user,username|max:12|string|alpha_num',
        'pin'      => 'required|numeric|digits:4'
    ], [
        'name.required'     => 'Full Name is Required',
        'email.required'    => 'E-mail is Required',
        'phone.required'    => 'Phone Number Required',
        'password.required' => 'Password Required',
        'username.required' => 'Username Required',
        'username.unique'   => 'Username already Taken',
        'phone.unique'      => 'Phone Number already Taken',
        'username.max'      => 'Username Maximum Length is 12',
        'email.unique'      => 'Email Already Taken',
        'password.min'      => 'Password Not Strong Enough',
        'name.min'          => 'Invalid Full Name',
        'name.max'          => 'Invalid Full Name',
        'phone.numeric'     => 'Phone Number Must be Numeric',
        'pin.required'      => 'Transaction Pin Required',
        'pin.numeric'       => 'Transaction Pin Must be Numeric',
        'pin.digits'        => 'Transaction Pin Must Be 4 Digits'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => $validator->errors()->first(),
            'status'  => 403
        ], 403);
    }
    if (substr($request->phone, 0, 1) != '0') {
        return response()->json([
            'message' => 'Invalid Phone Number',
            'status'  => 403
        ], 403);
    }

    // Referral validation
    if ($request->ref && !User::where('username', $request->ref)->exists()) {
        return response()->json([
            'message' => 'Invalid Referral Username. You can leave the referral box empty.',
            'status'  => 403
        ], 403);
    }
        $adexKey = AdexKey::first();
        $user = new User();
        $user->name       = $request->name;
        $user->username   = $request->username;
        $user->email      = $request->email;
        $user->phone      = $request->phone;
        $user->password   = Hash::make($request->password);
        $user->apikey     = $this->generateAPIKEY();
        $user->bal        = 0.00;
        $user->refbal     = 0.00;
        $user->ref        = $request->ref;
        $user->type       = 'SMART';
        $user->date       = now();
        $user->kyc        = 0;
        $user->status     = 0;
        $user->user_limit = $adexKey->default_limit ?? 5000;
        $user->pin        = $request->pin;
        $user->save();
        // Call external methods
        $this->walletService->generateAccount($user);
        $this->walletService->insertStock($user);

        $use     = Setting::first();
        $general = General::first();

        if ($use && $use->is_verify_email) {
            // send verification email
            $otp = random_int(100000, 999999);
            $user->update(['otp' => $otp]);

            $email_data = [
                'name'        => $user->name,
                'email'       => $user->email,
                'username'    => $user->username,
                'title'       => 'Account Verification',
                'pin'         => $user->pin,
                'sender_mail' => $general->app_email,
                'app_name'    => env('APP_NAME'),
                'otp'         => $otp
            ];
            MailController::send_mail($email_data, 'email.verify');
            return response()->json([
                'status'   => 'verify',
                'username' => $user->username,
                'user'     => $this->generateDetails($user)
            ]);
        }
        // else welcome email
        $user->update(['status' => 1]);

        $email_data = [
            'name'        => $user->name,
            'email'       => $user->email,
            'username'    => $user->username,
            'title'       => 'WELCOME EMAIL',
            'sender_mail' => $general->app_email,
            'system_email'=> $general->app_email,
            'app_name'    => $general->app_name,
            'pin'         => $user->pin,
        ];
        MailController::send_mail($email_data, 'email.welcome');

        return response()->json([
            'status'   => 'success',
            'username' => $user->username,
            'token'    => $this->tokenService->generateToken($user->id),
            'user'     => $this->generateDetails($user)
        ]);
}
    public function account(Request $request)
    {
        $user = auth()->user();
        $this->walletService->generateAccount($user);
        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => $this->generateDetails($user),
        ]);
    }
public function verifyPIN(Request $request){
           $user = User::where('email', $request->email)->first();
            if ($user) {
            if ($request->code && ($user->otp === $request->code || $user->pin === $request->code)) {
                    $data = [
                        'otp' => null
                    ];
                    $general = General::first();
                    $user->update($data);
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
                        'message' => 'account verified',
                        'token'    => $this->tokenService->generateToken($user->id),
                        'user'     => $this->generateDetails($user)
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'The PIN or OTP you entered is incorrect.'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to verify user'
                ])->setStatusCode(403);
            }
    }
    public function verify(Request $request){
            $user = User::where('email', $request->email)->first();
            if ($user) {
                if ($user->otp == $request->code) {
                    $data = [
                        'status' => 1,
                        'otp' => null
                    ];
                    $general = General::first();
                    $user->update($data);
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
                        'status' => 'success',
                        'message' => 'account verified',
                        'token'    => $this->tokenService->generateToken($user->id),
                        'user'     => $this->generateDetails($user)
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Invalid OTP'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to verify user'
                ])->setStatusCode(403);
            }
    }
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required'
        ], [
            'username.required' => 'Your Username or Phone Number or Email is Required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 403,
                'message' => $validator->errors()->first()
            ], 403);
        } else {
            $user = User::where('username', $request->username)->first();
            if (!$user) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Invalid Username and Password'
                ], 403);
            }
            $hash = substr(sha1(md5($request->password)), 3, 10);
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
             $use     = Setting::first();
            switch ((int) $user->status) {
                case 1:
                    $this->walletService->generateAccount($user);
                    $this->walletService->insertStock($user);
                    if($use && $use->verify_login){
                    return response()->json([
                        'status' => 'verify_login',
                        'message' => 'Login successful',
                        'user' => $this->generateDetails($user),
                    ]);
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
                    Auth::login($user);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Login successful',
                        'user' => $this->generateDetails($user),
                        'token' => $this->tokenService->generateToken($user->id)
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
                        'status' => 'verify',
                        'user' => $this->generateDetails($user),
                        'message' => "{$user->username}, your account is not yet verified."
                    ]);

                default:
                    return response()->json([
                        'status' => 403,
                        'message' => 'System is unable to verify user status.'
                    ], 403);
            }
        }
    }
public function resendOtp(Request $request){
            if (isset($request->id)) {
                $user = User::where('email', $request->id)->first();
                if ($user) {
                    $general = General::first();
                    $otp = random_int(100000, 999999);
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
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Unable to Detect User'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'An Error Occured'
                ])->setStatusCode(403);
            }
    }

    public function generateDetails(User $user)
    {
        $user_details = [
            'username' => $user->username,
            'name'  => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'bal' => number_format($user->bal, 2),
            'refbal' => number_format($user->refbal, 2),
            'kyc' => $user->kyc,
            'type' => $user->type,
            'profile_image' => $user->profile_image,
            'address' => $user->address,
            'webhook' => $user->webhook,
            'about' => $user->about,
            'apikey' => $user->apikey,
            'is_bvn' => is_null($user->bvn)?  false : true,
            'pin' => is_null($user->pin) ? null : '0000'
        ];
        return $user_details;
    }

      public function generateAPIKEY(): string
    {
        return Str::uuid()->toString();
    }
}
