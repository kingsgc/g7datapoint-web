<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\CableSelection;
use App\Models\CableID;

class IUCvad extends Controller
{
    public function IUC(Request $request)
    {
        $validated = $request->validate([
            'iuc'   => 'required|string',
            'cable' => 'required|integer'
        ]);

        $cable = CableID::where('plan_id', $validated['cable'])->first();
        if (!$cable) {
            return response()->json([
                'status'  => 'fail',
                'message' => 'Invalid cable ID'
            ], 403);
        }

        $cableSel = CableSelection::first();
        $methodName = strtolower($cable->cable_name);
        $vendorFunc = $cableSel->$methodName ?? null;
        if (Str::startsWith($vendorFunc, 'adex')) {
          $vendorFunc = 'adexHandler';
        }
        if (empty($vendorFunc) || !method_exists(new IUCsend(), $vendorFunc)) {
            return response()->json([
                'status'  => 'fail',
                'message' => 'Cable processing method not found'
            ], 500);
        }
        $adm = new IUCsend();
        $response = $adm->{$vendorFunc}([
            'iuc'    => $validated['iuc'],
            'cable'  => $validated['cable'],
            'method' => $cableSel->$methodName
        ]);
        if (!empty($response) && $response !== 'process') {
            return response()->json([
                'status' => 'success',
                'name'   => $response
            ]);
        }
        return response()->json([
            'status'  => 'fail',
            'message' => 'Invalid IUC number'
        ], 403);
    }
}
