<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Http\Controllers\MailController;
use App\Models\Data;
use App\Models\General;
use App\Models\User;
use App\Models\UserBank;
use App\Models\WalletFunding;
use App\Models\AppDownload;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class  SecureController extends Controller{
    public function UserStock(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'stock' => WalletFunding::where('user_id', auth()->user()->id)->first()
        ]);
    }
public function UserEditStock(Request $request){
    $userId = auth()->id();
    $fields = [
        'mtn_sme', 'mtn_cg', 'mtn_g',
        'glo_sme', 'glo_cg', 'glo_g',
        'airtel_sme', 'airtel_cg', 'airtel_g',
        'mobile_sme', 'mobile_cg', 'mobile_g',
    ];
    $data = [];
    foreach ($fields as $field) {
        // Normalize: if request value is truthy (true/1) → 1 else 0
        $data[$field] = ($request->input($field) == true || $request->input($field) == 1) ? 1 : 0;
    }
    WalletFunding::where('user_id', $userId)->update($data);
    return response()->json([
        'status'  => 'success',
        'message' => 'Update successful',
    ]);
}
    public function UserProfile(Request $request){
        $user = User::find(auth()->user()->id);
        // Clean up "null" strings to actual null
        $clean = collect($request->only(['address', 'about', 'webhook']))
            ->map(fn($val) => $val === 'null' ? null : $val);

        $validator = Validator::make(array_merge($clean->toArray(), [
            'profile_image' => $request->file('profile_image')
        ]), [
            'address' => 'nullable|string|max:255',
            'about' => 'nullable|string|max:1000',
            'webhook' => 'nullable|url|max:255',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 403);
        }

        $profileImagePath = $user->profile_image;

        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $filename = $user->username . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/avatars', $filename);
            $profileImagePath = str_replace('public/', '', $path);
        }
        $user->update([
            'address' => $clean['address'],
            'about' => $clean['about'],
            'webhook' => $clean['webhook'],
            'profile_image' => $profileImagePath,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Profile updated']);
    }
    public function ResetPasswordUser(Request $request)
    {
        $adex = User::find(auth()->user()->id);
        $main_validator = validator::make($request->all(), [
            'oldPassword' => 'required',
            'newPassword' => "required",
            'confirmNewPassword' => 'required',
        ]);
        if ($main_validator->fails()) {
            return response()->json([
                'message' => $main_validator->errors()->first(),
                'status' => 403
            ], 403);
        } else {
            $hash = substr(sha1(md5($request->oldPassword)), 3, 10);
            $mdpass = md5($request->oldPassword);
            if ((password_verify($request->oldPassword, $adex->password)) xor ($request->oldPassword == $adex->password) xor ($hash == $adex->password) xor ($mdpass == $adex->password)) {
                $password =  password_hash($request->newPassword,  PASSWORD_DEFAULT, array('cost' => 16));
                $adex->update(['password' => $password]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'password updated'
                ]);
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Incorrect Old Password'
                ])->setStatusCode(403);
            }
        }
    }
    public function ChangePin(Request $request)
    {
        $adex = User::find(auth()->user()->id);
        $main_validator = validator::make($request->all(), [
            'oldpin' => 'required',
            'newpin' => "required|numeric|digits:4",
            'confirmpin' => 'required|numeric',
        ]);
        if ($main_validator->fails()) {
            return response()->json([
                'message' => $main_validator->errors()->first(),
                'status' => 403
            ])->setStatusCode(403);
        } else {
            if (($request->oldpin == $adex->pin)) {
                $adex->update(['pin' => $request->newpin]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Transaction Pin updated'
                ]);
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Incorrect Old Transaction Pin'
                ])->setStatusCode(403);
            }
        }
    }
    public function CreatePin(Request $request)
    {
        $adex = User::find(auth()->user()->id);
        $main_validator = validator::make($request->all(), [
            'newpin' => "required|numeric|digits:4",
            'confirmpin' => 'required|numeric',
        ]);
        if ($main_validator->fails()) {
            return response()->json([
                'message' => $main_validator->errors()->first(),
                'status' => 403
            ])->setStatusCode(403);
        } else {
            if (($adex->pin == null || $adex->pin == '')) {
                $adex->update(['pin' => $request->newpin]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Transaction Pin Created'
                ]);
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'You Are Not Authorized Kindly Reload the Page'
                ])->setStatusCode(403);
            }
        }
    }
    public function UserAccountDetails(Request $request)
    {
        $adex = auth()->user();
        $main_validator = validator::make($request->all(), [
            'bank_name' => 'required',
            'bank_code' => 'required',
            'account_number' => 'required'
        ]);
        if ($main_validator->fails()) {
            return response()->json([
                'message' => $main_validator->errors()->first(),
                'status' => 403
            ], 403);
        } else {
            // monnify verify account details
            $send_request = "https://api.monnify.com/api/v1/disbursements/account/validate?accountNumber=$request->account_number&bankCode=$request->bank_code";
            $response = Http::get($send_request);
            $json_response = $response->json();
            if (!empty($json_response)) {
                if ($json_response['requestSuccessful'] == true) {
                    $account = UserBank::where('user_id', $adex->id)->first();
                    if ($account) {
                        $account->update(['bank' => $request->bank_name, 'bank_name' => $json_response['responseBody']['accountName'], 'bank_code' => $request->bank_code, 'account_number' => $request->account_number]);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
                    } else {
                        UserBank::create(['bank' => $request->bank_name, 'bank_name' => $json_response['responseBody']['accountName'], 'bank_code' => $request->bank_code, 'account_number' => $request->account_number, 'username' => $adex->username, 'date' => now(), 'user_id' => $adex->id]);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Inavlid Account Details'
                    ], 403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Inavlid Account Details'
                ], 403);
            }
        }
    }
    public function UsersAccountDetails(Request $request)
    {
        $adex = auth()->user();
        $account = UserBank::where('user_id', $adex->id)->first();
        if ($account) {
            return response()->json([
                'status' => 'success',
                'bank' =>  $account
            ]);
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'not yet availabale'
            ])->setStatusCode(403);
        }
    }
    public function DataPurchased(Request $request)
    {
        $user = auth()->user();
        $data_purchase = Data::where(['user_id' => $user->id, 'plan_status' => 1])->whereDate('plan_date',  Carbon::today())->get();
        $total_gb = 0;
        $calculate_gb = '0GB';
        foreach ($data_purchase as $data) {
            $plans = $data->plan_name;
            $check_gb = strtoupper(substr($plans, -2));
            $numeric_part = preg_replace('/[^0-9.]/', '', substr($plans, 0, -2));

            if (!is_numeric($numeric_part)) {
                continue;
            }

            if ($check_gb === 'MB') {
                $gb = $numeric_part / 1000;
            } elseif ($check_gb === 'GB') {
                $gb = (float) $numeric_part;
            } elseif ($check_gb === 'TB') {
                $gb = ceil($numeric_part * 1000);
            } else {
                continue;
            }

            $total_gb += $gb;
        }
        if ($total_gb >= 1000) {
            $calculate_gb = $total_gb / 1000 . 'TB';
        } else {
            $calculate_gb =  $total_gb . 'GB';
        }
        return response()->json([
            'status' => 'success',
            'data_purchased' => $calculate_gb
        ]);
    }
    public function StockBalance(Request $request){
         return response()->json([
         'stock_balance' =>  WalletFunding::where('user_id', auth()->user()->id)->get()
        ]);
                
    }
    public function SOFTWARE(Request $request)
    {
        return response()->json([
                'status' => 'success',
                'app' => AppDownload::get()
            ]);
    }
    public function ResetPassword(Request $request){
         $user = User::where(['status' => 1, 'email' => $request->email])->first();
            if ($user) {
                $otp = mt_rand(1000000, 9999999) . mt_rand(1000000, 9999999);
               $user->update(['otp' => $otp]);
               $setting = General::first();
                $email_data = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'title' => 'RESET PASSWORD',
                    'sender_mail' => $setting->app_email,
                    'user_email' => $user->email,
                    'app_name' => $setting->app_name,
                    'date' => now(),
                    'reset_url' => url()->to( '//app.' . request()->getHost()  . "/resetpassword/verify/adex/{$otp}/reset" ),
                    'app_phone' =>  $setting->app_phone
                ];
                MailController::send_mail($email_data, 'email.reset-password');
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Invalid Email Address'
                ], 403);
            }
    }
public function ChangePPassword(Request $request){
            $user =  User::where(['status' => 1, 'otp' => $request->id]);
            if ($user) {
                $main_validator = validator::make($request->all(), [
                    'password' => 'required|min:8',
                    'confirmpassword' => 'required|min:8',
                ]);
                if ($main_validator->fails()) {
                    return response()->json([
                        'message' => $main_validator->errors()->first(),
                        'status' => 403
                    ])->setStatusCode(403);
                } else {
                    $password =  password_hash($request->password,  PASSWORD_DEFAULT, array('cost' => 16));
                    $user->update(['otp' => null, 'password' => $password]);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'password reseted'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Link Expired'
                ])->setStatusCode(403);
            }
    }
}
