<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BillPlan;
use App\Models\BillSelection;
use Illuminate\Support\Str;

class MeterVerify extends Controller
{
    public function Check(Request $request)
    {
        // Validate request
        $request->validate([
            'disco'         => 'required|integer|exists:bill_plan,plan_id',
            'meter_number'  => 'required|string',
            'meter_type'    => 'required|string|in:prepaid,postpaid',
        ], [
            'disco.required'        => 'Disco ID Required',
            'disco.exists'          => 'Invalid Disco Id',
            'meter_number.required' => 'Meter Number Required',
            'meter_type.required'   => 'Meter type Required',
            'meter_type.in'         => 'Invalid meter type',
        ]);
        // Fetch active bill source
        $billSel = BillSelection::first();
        if (!$billSel || empty($billSel->bill)) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Bill source not configured',
            ], 500);
        }

        // Send verification request
        $verifyMeter = new MeterSend();
        $method = $billSel->bill;
        $data = [
            'disco'         => $request->disco,
            'meter_number'  => $request->meter_number,
            'meter_type'    => strtolower($request->meter_type),
            'method' => $method
        ];
        if (Str::startsWith($method, 'adex')) {
           $method = 'adexHandler';
        }
        if (!method_exists($verifyMeter, $method)) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Invalid bill source method',
            ], 500);
        }
        $response = $verifyMeter->$method($data);
        if (!empty($response)) {
            return response()->json([
                'status' => 'success',
                'name'   => $response,
            ]);
        }
        return response()->json([
            'status'  => 'fail',
            'message' => 'Invalid Meter Number',
        ], 403);
    }
}
