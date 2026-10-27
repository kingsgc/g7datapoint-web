<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class  Banks extends Controller
{
    public function GetBanksArray(Request $request)
    {
        $auth_user = auth()->user();
        $banks_array = [];
        if (!is_null($auth_user->palmpay)) {
                $banks_array[] = [
                    "name" => "PALMPAY",
                    "account" => $auth_user->palmpay,
                    "accountType" => $auth_user->palmpay === null,
                    'charges' => $this->getXixapayChargeText(),
                ];
            }
            if (!is_null($auth_user->kolo_moni)) {
                $banks_array[] = [
                    "name" => "KOLOMONI MFB",
                    "account" => $auth_user->kolo_moni,
                    "accountType" => $auth_user->kolo_moni === null,
                    'charges' => $this->getXixapayChargeText(),
                ];
            }
             if (!is_null($auth_user->safe)) {
                $banks_array[] = [
                    "name" => "SAFEHAVEN MFB",
                    "account" => $auth_user->safe,
                    "accountType" => $auth_user->safe === null,
                   'charges' => $this->getXixapayChargeText(),
                ];
            }


        return response()->json(['banks' => $banks_array]);
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
