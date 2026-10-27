<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DataCardPlan;
use App\Models\RechargeCardPlan;
use App\Models\Network;
use Illuminate\Support\Facades\Validator;
class  NewStock extends Controller{
public function UserDataCardPlan(Request $request){
    $validator = Validator::make($request->all(), [
        'network' => 'required',
    ]);
    if ($validator->fails()) {
        return response()->json([
            'message' => $validator->errors()->first(),
            'status'  => 403
        ], 403);
    }
    $user = auth()->user();
    switch ($user->type) {
        case 'SMART':
            $userType = 'smart';
            break;
        case 'AGENT':
            $userType = 'agent';
            break;
        case 'AWUF':
            $userType = 'awuf';
            break;
        case 'API':
            $userType = 'api';
            break;
        default:
            $userType = 'special';
            break;
    }
    $networkInfo = Network::where('plan_id', $request->network)->first();
    if (!$networkInfo) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Network not found'
        ], 404);
    }
    $plansQuery = DataCardPlan::where([
        'network'     => $networkInfo->network,
        'plan_status' => 1
    ]);
    $dataPlans = [];
    if ($plansQuery->exists()) {
        foreach ($plansQuery->get() as $plan) {
            $dataPlans[] = [
                'name'    => $plan->name . ' ' . $plan->plan_size . ' ' . $plan->plan_type . ' = ₦' . number_format($plan->{$userType}, 2) . ' ' . $plan->plan_day,
                'plan_id' => $plan->plan_id,
                'amount'  => $plan->{$userType}
            ];
        }
    }
    return response()->json([
        'status' => 'success',
        'plan'   => $dataPlans
    ]);
}
public function UserRechargeCardPlan(Request $request)
{
    $validator = validator::make($request->all(), [
        'network' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => $validator->errors()->first(),
            'status'  => 403
        ], 403);
    }

    $user = auth()->user();

    $user_type = match ($user->type) {
        'SMART'  => 'smart',
        'AGENT'  => 'agent',
        'AWUF'   => 'awuf',
        'API'    => 'api',
        default  => 'special',
    };

    $network = Network::where('plan_id', $request->network)->first();
    if (!$network) {
        return response()->json([
            'message' => 'Invalid network selection.',
            'status'  => 404
        ], 404);
    }

    $plans = RechargeCardPlan::where([
        'network'     => $network->network,
        'plan_status' => 1
    ])->get();

    $data_plan = [];
    foreach ($plans as $plan) {
        $data_plan[] = [
            'name'    => $plan->name . ' = ₦' . number_format($plan->$user_type, 2),
            'plan_id' => $plan->plan_id,
            'amount'  => $plan->$user_type
        ];
    }

    return response()->json([
        'status' => 'success',
        'plan'   => $data_plan
    ]);
}
}
