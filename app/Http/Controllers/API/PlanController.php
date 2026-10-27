<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CableCharge;
use App\Models\CableID;
use App\Models\CablePlan;
use App\Models\DataPlan;
use App\Models\ExamId;
use App\Models\Network;
use App\Models\ResultCharge;
use App\Models\RechargeCardPlan;
use App\Models\DataCardPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class  PlanController extends Controller
{

    public function DataPlan(Request $request)
    {
        $adex = auth()->user();
        // validate form
        $main_validator = validator::make($request->all(), [
            'network' => 'required',
            'network_type' => 'required',
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
            $all_plan = DataPlan::where(['network' => $get_network->network, 'plan_type' => $request->network_type, 'plan_status' => 1]);
            if ($all_plan->count() > 0) {
                foreach ($all_plan->get() as $adex => $plan) {
                    $data_plan[] =  ['name' => $plan->plan_name . $plan->plan_size . ' ' . $plan->plan_type . ' = ₦' . number_format($plan->$user_type, 2) . ' ' . $plan->plan_day, 'plan_id' => $plan->plan_id, 'amount' => $plan->$user_type];;
                }
            } else {
                $data_plan = [];
            }
            return response()->json([
                'status' => 'success',
                'plan' => $data_plan
            ]);
        }
    }
     public function DataCard(Request $request)
    {
       
                    $adex = auth()->user();
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


                    $all_plan = DataCardPlan::where(['plan_status' => 1]);
                    if ($all_plan->count() > 0) {
                        foreach ($all_plan->get() as $adex => $plan) {
                            $data_plan[] =  ['plan_name' => $plan->name . $plan->plan_size, 'plan_id' => $plan->plan_id, 'amount' => number_format($plan->$user_type, 2), 'plan_type' => $plan->plan_type, 'plan_day' => $plan->plan_day, 'network' => $plan->network, 'load_pin' => $plan->load_pin, 'check_balance' => $plan->check_balance];;
                        }
                    } else {
                        $data_plan = [];
                    }
                    return response()->json([
                        'status' => 'success',
                        'plan' => $data_plan
                    ]);
              
    }
     public function RechargeCard(Request $request)
    {
        $adex = auth()->user();

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

                    $all_plan = RechargeCardPlan::where(['plan_status' => 1]);
                    if ($all_plan->count() > 0) {
                        foreach ($all_plan->get() as $adex => $plan) {
                            $data_plan[] =  ['name' => $plan->name, 'plan_id' => $plan->plan_id, 'amount' => number_format($plan->$user_type, 2),  'network' => $plan->network, 'load_pin' => $plan->load_pin, 'check_balance' => $plan->check_balance];;
                        }
                    } else {
                        $data_plan = [];
                    }
                    return response()->json([
                        'status' => 'success',
                        'plan' => $data_plan
                    ]);
               
            
    }
    public function CablePlan(Request $request)
    {
        if (isset($request->id)) {
            $cable_name = CableID::where('plan_id', $request->id)->first();
            return response()->json([
                'status' => 'suucess',
                'plan' => CablePlan::where(['cable_name' => $cable_name->cable_name, 'plan_status' => 1])->select('cable_name', 'plan_name', 'plan_price', 'plan_id')->get()
            ]);
        }
    }
    public function CableCharges(Request $request)
    {
        if (isset($request->id)) {
            if (CablePlan::where('plan_id', $request->id)->count() == 1) {
                $cable = CablePlan::where('plan_id', $request->id)->first();
                $amount = $cable->plan_price;
                $cable_name = strtolower($cable->cable_name);
                $cable_setting = CableCharge::first();
                $discount = 0;
                if ($cable_setting->direct == 1) {
                    $discount = ($amount / 100) * $cable_setting->$cable_name;
                } 
                return response()->json([
                    'status' => 'suucess',
                    'amount' => $amount,
                    'charges' => $discount
                ]);
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to calculate'
                ])->setStatusCode(403);
            }
        }
    }
    public function DataList(Request $request)
    {
        $adex = auth()->user();
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
        $all_plan = DataPlan::where(['plan_status' => 1]);
        foreach ($all_plan->get() as $adex => $plan) {
            $data_plan[] =  ['plan_name' => $plan->plan_name . $plan->plan_size, 'plan_id' => $plan->plan_id, 'amount' => number_format($plan->$user_type, 2), 'plan_type' => $plan->plan_type, 'plan_day' => $plan->plan_day, 'network' => $plan->network];;
        }
        return response()->json([
            'status' => 'success',
            'plan' => $data_plan
        ]);
    }
    public function CableList(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'plan' => CablePlan::where('plan_status', 1)->select('cable_name', 'plan_name', 'plan_price', 'plan_id')->get()
        ]);
    }
    public function ExamList(Request $request)
    {
        $exam_list = [];
        $exam_id = ExamId::get();
        $exam_price = ResultCharge::first();
        foreach ($exam_id as $exam) {
            if ($exam->exam_name == 'WAEC') {
                $exam_list[] = ['exam_name' => $exam->exam_name, 'plan_id' => $exam->plan_id, 'amount' => '₦' . number_format($exam_price->waec, 2)];
            }
            if ($exam->exam_name == 'NECO') {
                $exam_list[] = ['exam_name' => $exam->exam_name, 'plan_id' => $exam->plan_id, 'amount' => '₦' . number_format($exam_price->neco, 2)];
            }
            if ($exam->exam_name == 'NABTEB') {
                $exam_list[] = ['exam_name' => $exam->exam_name, 'plan_id' => $exam->plan_id, 'amount' => '₦' . number_format($exam_price->nabteb, 2)];
            }
        }
        return response()->json([
            'status' => 'success',
            'plan' => $exam_list
        ]);
    }
    public function HomeData(Request $request)
    {
            return response()->json([
                'status' => 'success',
                'mtn' => DataPlan::where(['network' => 'MTN', 'plan_status' => 1])->select('plan_name', 'network', 'plan_size', 'plan_day', 'smart')->orderBy('smart', 'asc')->get(),
                'glo' => DataPlan::where(['network' => 'GLO', 'plan_status' => 1])->select('plan_name', 'network', 'plan_size', 'plan_day', 'smart')->orderBy('smart', 'asc')->get(),
                'airtel' => DataPlan::where(['network' => 'AIRTEL', 'plan_status' => 1])->select('plan_name', 'network', 'plan_size', 'plan_day', 'smart')->orderBy('smart', 'asc')->get(),
                'mobile' => DataPlan::where(['network' => '9MOBILE', 'plan_status' => 1])->select('plan_name', 'network', 'plan_size', 'plan_day', 'smart')->orderBy('smart', 'asc')->get()
            ]);
    }
}
