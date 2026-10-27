<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Data;
use App\Models\Airtime;
use App\Models\Cable;
use App\Models\Exam;
use App\Models\BulkSms;
use App\Models\Deposit;
use App\Models\History; 
use App\Models\Cash;
use App\Models\Bill;
use App\Models\RechargeCard;
use App\Models\DataCard;
use Illuminate\Database\Eloquent\Builder;

class TransactionCalculator extends Controller
{   

     public function User(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            // Handle unauthenticated user case, e.g., return an error response
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        return $this->calculateTransactionSummary($request, $user->id);
    }
    public function getAdminTransactionSummary(Request $request): \Illuminate\Http\JsonResponse
    {
        $targetUserId = $request->input('user_id') ? (int)$request->input('user_id') : null;

        return $this->calculateTransactionSummary($request, $targetUserId);
    }
    public function calculateTransactionSummary(Request $request, ?int $userId): \Illuminate\Http\JsonResponse
    {
        $status = strtoupper($request->status);
        $from = $request->from ? Carbon::parse($request->from, 'Africa/Lagos')->startOfDay() : null;
        $to = $request->to ? Carbon::parse($request->to, 'Africa/Lagos')->endOfDay() : null;
        $days = null;
        switch ($status) {
            case 'TODAY':
                $days = 0;
                break;
            case 'YESTERDAY':
                $days = -1;
                break;
            case '7DAYS':
                $days = 7;
                break;
            case '30DAYS':
                $days = 30;
                break;
            case 'ALL TIME':
                break;
            default:
                if (!$from || !$to) {
                    return response()->json(['message' => 'Start date and end date are required for a custom date range'], 403);
                }
                break;
        }
        $buildBaseQuery = function (string $modelClass, string $column, ?int $days, ?Carbon $from, ?Carbon $to, ?int $queryUserId = null): Builder {
          $query = $modelClass::query();
           if ($queryUserId !== null) {
                $query->where('user_id', $queryUserId);
            }
           if ($modelClass !== Deposit::class) {
                $query->where('plan_status', 1);
            }
            if ($modelClass === Deposit::class) {
                $query->where('status', 1);
            }
            if ($modelClass === History::class) {
                $query->where(function ($q) {
                    $q->where('role', '!=', 'credit')
                      ->where('plan_status', '!=', 2);
                });
            }
            if ($days !== null) {
                if ($days === 0) {
                    $query->whereDate($column, Carbon::now('Africa/Lagos'));
                } elseif ($days === -1) { 
                    $query->whereDate($column, Carbon::yesterday('Africa/Lagos'));
                } else {
                    $query->whereDate($column, '>', Carbon::now('Africa/Lagos')->subDays($days));
                }
            } elseif ($from && $to) {
                $query->whereBetween($column, [$from, $to]);
            }
            return $query;
        };
        $dataTotals = [
            'MTN' => ['GIFTING' => ['gb' => 0.0, 'bal' => 0.0], 'SME' => ['gb' => 0.0, 'bal' => 0.0], 'COOPERATE GIFTING' => ['gb' => 0.0, 'bal' => 0.0]],
            'AIRTEL' => ['GIFTING' => ['gb' => 0.0, 'bal' => 0.0], 'SME' => ['gb' => 0.0, 'bal' => 0.0], 'COOPERATE GIFTING' => ['gb' => 0.0, 'bal' => 0.0]],
            'GLO' => ['GIFTING' => ['gb' => 0.0, 'bal' => 0.0], 'SME' => ['gb' => 0.0, 'bal' => 0.0], 'COOPERATE GIFTING' => ['gb' => 0.0, 'bal' => 0.0]],
            '9MOBILE' => ['GIFTING' => ['gb' => 0.0, 'bal' => 0.0], 'SME' => ['gb' => 0.0, 'bal' => 0.0], 'COOPERATE GIFTING' => ['gb' => 0.0, 'bal' => 0.0]],
        ];
        $dataAggregations = $buildBaseQuery(Data::class, 'created_at', $days, $from, $to, $userId)
            ->select('network', 'network_type', 'amount', 'plan_name')
            ->get();

        foreach ($dataAggregations as $data) {
            $network = strtoupper($data->network);
            $type = strtoupper($data->network_type);
            if (isset($dataTotals[$network][$type])) {
                $gb = $this->calculateDataInGB($data->plan_name);
                $dataTotals[$network][$type]['gb'] += $gb;
                $dataTotals[$network][$type]['bal'] += (float)$data->amount;
            }
        }
        $airtimeTotals = [
            'MTN' => ['VTU' => ['amount' => 0.0, 'discount' => 0.0], 'SNS' => ['amount' => 0.0, 'discount' => 0.0]],
            'AIRTEL' => ['VTU' => ['amount' => 0.0, 'discount' => 0.0], 'SNS' => ['amount' => 0.0, 'discount' => 0.0]],
            'GLO' => ['VTU' => ['amount' => 0.0, 'discount' => 0.0], 'SNS' => ['amount' => 0.0, 'discount' => 0.0]],
            '9MOBILE' => ['VTU' => ['amount' => 0.0, 'discount' => 0.0], 'SNS' => ['amount' => 0.0, 'discount' => 0.0]],
        ];
        $airtimeAggregations = $buildBaseQuery(Airtime::class, 'created_at', $days, $from, $to, $userId)
            ->select('network', 'network_type', 'amount', 'discount')
            ->get();

        foreach ($airtimeAggregations as $airtime) {
            $network = strtoupper($airtime->network);
            $type = strtoupper($airtime->network_type);
            if (isset($airtimeTotals[$network][$type])) {
                $airtimeTotals[$network][$type]['amount'] += (float)$airtime->amount;
                $airtimeTotals[$network][$type]['discount'] += (float)$airtime->discount;
            }
        }

        $cableTotals = ['DSTV' => ['amount' => 0.0, 'charges' => 0.0], 'GOTV' => ['amount' => 0.0, 'charges' => 0.0], 'STARTIME' => ['amount' => 0.0, 'charges' => 0.0]];
        $cableAggregations = $buildBaseQuery(Cable::class, 'created_at', $days, $from, $to, $userId)
            ->select('cable_name', 'amount', 'charges')
            ->get();

        foreach ($cableAggregations as $cable) {
            $cableName = strtoupper($cable->cable_name);
            if (isset($cableTotals[$cableName])) {
                $cableTotals[$cableName]['amount'] += (float)$cable->amount;
                $cableTotals[$cableName]['charges'] += (float)$cable->charges;
            }
        }

        $examTotals = ['WAEC' => ['amount' => 0.0, 'quantity' => 0], 'NECO' => ['amount' => 0.0, 'quantity' => 0], 'NABTEB' => ['amount' => 0.0, 'quantity' => 0]];
        $examAggregations = $buildBaseQuery(Exam::class, 'created_at', $days, $from, $to, $userId)
            ->select('exam_name', 'amount', 'quantity')
            ->get();

        foreach ($examAggregations as $exam) {
            $examName = strtoupper($exam->exam_name);
            if (isset($examTotals[$examName])) {
                $examTotals[$examName]['amount'] += (float)$exam->amount;
                $examTotals[$examName]['quantity'] += (int)$exam->quantity;
            }
        }
        $rechargeCardTotals = [
            'MTN' => ['amount' => 0.0, 'quantity' => 0],
            'AIRTEL' => ['amount' => 0.0, 'quantity' => 0],
            'GLO' => ['amount' => 0.0, 'quantity' => 0],
            '9MOBILE' => ['amount' => 0.0, 'quantity' => 0],
        ];
        $rechargeCardAggregations = $buildBaseQuery(RechargeCard::class, 'created_at', $days, $from, $to, $userId)
            ->select('network', 'amount', 'quantity')
            ->get();
        foreach ($rechargeCardAggregations as $card) {
            $network = strtoupper($card->network);
            if (isset($rechargeCardTotals[$network])) {
                $rechargeCardTotals[$network]['amount'] += (float)$card->amount;
                $rechargeCardTotals[$network]['quantity'] += (int)$card->quantity;
            }
        }
        $dataCardTotals = [
            'MTN' => ['amount' => 0.0, 'quantity' => 0, ],
            'AIRTEL' => ['amount' => 0.0, 'quantity' => 0],
            'GLO' => ['amount' => 0.0, 'quantity' => 0],
            '9MOBILE' => ['amount' => 0.0, 'quantity' => 0],
        ];
        $dataCardAggregations = $buildBaseQuery(DataCard::class, 'created_at', $days, $from, $to, $userId)
            ->select('network', 'amount', 'quantity')
            ->get();
        foreach ($dataCardAggregations as $dataCard) {
            $network = strtoupper($dataCard->network);
            if (isset($dataCardTotals[$network])) {
                $dataCardTotals[$network]['amount'] += (float)$dataCard->amount;
                $dataCardTotals[$network]['quantity'] += (int)$dataCard->quantity;
            }
        }
        $bulksms_sum = (float)$buildBaseQuery(BulkSms::class, 'created_at', $days, $from, $to, $userId)->sum('amount');
        $bill_sum = (float)$buildBaseQuery(Bill::class, 'created_at', $days, $from, $to, $userId)->sum('amount');
        $cash_amount_sum = (float)$buildBaseQuery(Cash::class, 'created_at', $days, $from, $to, $userId)->sum('amount');
        $cash_pay_sum = (float)$buildBaseQuery(Cash::class, 'created_at', $days, $from, $to, $userId)->sum('amount_credit');
        $deposit_amount_sum = (float)$buildBaseQuery(Deposit::class, 'created_at', $days, $from, $to, $userId)->sum('amount');
        $deposit_charges_sum = (float)$buildBaseQuery(Deposit::class, 'created_at', $days, $from, $to, $userId)->sum('charges');
        $money_spent_sum = (float)$buildBaseQuery(History::class, 'created_at', $days, $from, $to, $userId)->sum('amount');
        $adex_in = $deposit_amount_sum;
        $adex_out = $money_spent_sum;
        $total_m = $adex_in + $adex_out;
        $adex_in_trans_percent = $total_m != 0 ? ($adex_in / $total_m) * 100 : 0.0;
        $adex_out_trans_percent = $total_m != 0 ? ($adex_out / $total_m) * 100 : 0.0;
        $mtn_cg = $this->formatDataVolume($dataTotals['MTN']['COOPERATE GIFTING']['gb']);
        $mtn_cg_bal = $dataTotals['MTN']['COOPERATE GIFTING']['bal'];
        $mtn_sme_bal = $dataTotals['MTN']['SME']['bal'];
        $mtn_g_bal = $dataTotals['MTN']['GIFTING']['bal'];
        $mtn_sme = $this->formatDataVolume($dataTotals['MTN']['SME']['gb']);
        $mtn_g = $this->formatDataVolume($dataTotals['MTN']['GIFTING']['gb']);

        $airtel_cg = $this->formatDataVolume($dataTotals['AIRTEL']['COOPERATE GIFTING']['gb']);
        $airtel_cg_bal = $dataTotals['AIRTEL']['COOPERATE GIFTING']['bal'];
        $airtel_sme_bal = $dataTotals['AIRTEL']['SME']['bal'];
        $airtel_g_bal = $dataTotals['AIRTEL']['GIFTING']['bal'];
        $airtel_sme = $this->formatDataVolume($dataTotals['AIRTEL']['SME']['gb']);
        $airtel_g = $this->formatDataVolume($dataTotals['AIRTEL']['GIFTING']['gb']);

        $glo_cg = $this->formatDataVolume($dataTotals['GLO']['COOPERATE GIFTING']['gb']);
        $glo_cg_bal = $dataTotals['GLO']['COOPERATE GIFTING']['bal'];
        $glo_sme_bal = $dataTotals['GLO']['SME']['bal'];
        $glo_g_bal = $dataTotals['GLO']['GIFTING']['bal'];
        $glo_sme = $this->formatDataVolume($dataTotals['GLO']['SME']['gb']);
        $glo_g = $this->formatDataVolume($dataTotals['GLO']['GIFTING']['gb']);

        $mobile_cg = $this->formatDataVolume($dataTotals['9MOBILE']['COOPERATE GIFTING']['gb']);
        $mobile_cg_bal = $dataTotals['9MOBILE']['COOPERATE GIFTING']['bal'];
        $mobile_sme_bal = $dataTotals['9MOBILE']['SME']['bal'];
        $mobile_g_bal = $dataTotals['9MOBILE']['GIFTING']['bal'];
        $mobile_sme = $this->formatDataVolume($dataTotals['9MOBILE']['SME']['gb']);
        $mobile_g = $this->formatDataVolume($dataTotals['9MOBILE']['GIFTING']['gb']);
        $mtn_vtu = $airtimeTotals['MTN']['VTU']['amount'];
        $mtn_vtu_d = $airtimeTotals['MTN']['VTU']['discount'];
        $mtn_sns = $airtimeTotals['MTN']['SNS']['amount'];
        $mtn_sns_d = $airtimeTotals['MTN']['SNS']['discount'];

        $airtel_vtu = $airtimeTotals['AIRTEL']['VTU']['amount'];
        $airtel_vtu_d = $airtimeTotals['AIRTEL']['VTU']['discount'];
        $airtel_sns = $airtimeTotals['AIRTEL']['SNS']['amount'];
        $airtel_sns_d = $airtimeTotals['AIRTEL']['SNS']['discount'];

        $glo_vtu = $airtimeTotals['GLO']['VTU']['amount'];
        $glo_vtu_d = $airtimeTotals['GLO']['VTU']['discount'];
        $glo_sns = $airtimeTotals['GLO']['SNS']['amount'];
        $glo_sns_d = $airtimeTotals['GLO']['SNS']['discount'];

        $mobile_vtu = $airtimeTotals['9MOBILE']['VTU']['amount'];
        $mobile_vtu_d = $airtimeTotals['9MOBILE']['VTU']['discount'];
        $mobile_sns = $airtimeTotals['9MOBILE']['SNS']['amount'];
        $mobile_sns_d = $airtimeTotals['9MOBILE']['SNS']['discount'];

        // Cable totals
        $dstv = $cableTotals['DSTV']['amount'];
        $dstv_c = $cableTotals['DSTV']['charges'];
        $gotv = $cableTotals['GOTV']['amount'];
        $gotv_c = $cableTotals['GOTV']['charges'];
        $startime = $cableTotals['STARTIME']['amount'];
        $startime_c = $cableTotals['STARTIME']['charges'];

        // Exam totals
        $waec = $examTotals['WAEC']['amount'];
        $waec_q = $examTotals['WAEC']['quantity'];
        $neco = $examTotals['NECO']['amount'];
        $neco_q = $examTotals['NECO']['quantity'];
        $nabteb = $examTotals['NABTEB']['amount'];
        $nabteb_q = $examTotals['NABTEB']['quantity'];

        // recharge card
        $mtn_recharge_amount = $rechargeCardTotals['MTN']['amount'];
        $mtn_recharge_quantity = $rechargeCardTotals['MTN']['quantity'];
        $airtel_recharge_amount = $rechargeCardTotals['AIRTEL']['amount'];
        $airtel_recharge_quantity = $rechargeCardTotals['AIRTEL']['quantity'];
        $glo_recharge_amount = $rechargeCardTotals['GLO']['amount'];
        $glo_recharge_quantity = $rechargeCardTotals['GLO']['quantity'];
        $mobile_recharge_amount = $rechargeCardTotals['9MOBILE']['amount'];
        $mobile_recharge_quantity = $rechargeCardTotals['9MOBILE']['quantity'];

        // data card summeries

        $mtn_datacard_amount = $dataCardTotals['MTN']['amount'];
        $mtn_datacard_quantity = $dataCardTotals['MTN']['quantity'];
        $airtel_datacard_amount = $dataCardTotals['AIRTEL']['amount'];
        $airtel_datacard_quantity = $dataCardTotals['AIRTEL']['quantity'];
        $glo_datacard_amount = $dataCardTotals['GLO']['amount'];
        $glo_datacard_quantity = $dataCardTotals['GLO']['quantity'];
        $mobile_datacard_amount = $dataCardTotals['9MOBILE']['amount'];
        $mobile_datacard_quantity = $dataCardTotals['9MOBILE']['quantity'];

        // Simple sums
        $bulksms = $bulksms_sum;
        $bill = $bill_sum;
        $cash_amount = $cash_amount_sum;
        $cash_pay = $cash_pay_sum;
        $deposit_amount = $deposit_amount_sum;
        $deposit_charges = $deposit_charges_sum;
        $money_spent = $money_spent_sum;

        return response()->json([
            'status' => 'success',
            // Data summaries
            'mtn_cg' =>  $mtn_cg,
            'mtn_cg_bal' => number_format($mtn_cg_bal, 2),
            'mtn_sme_bal' => number_format($mtn_sme_bal, 2),
            'mtn_g_bal' => number_format($mtn_g_bal, 2),
            'mtn_sme' =>  $mtn_sme,
            'mtn_g' =>  $mtn_g,

            'airtel_cg' =>  $airtel_cg,
            'airtel_cg_bal' => number_format($airtel_cg_bal, 2),
            'airtel_sme_bal' => number_format($airtel_sme_bal, 2),
            'airtel_g_bal' => number_format($airtel_g_bal, 2),
            'airtel_sme' =>  $airtel_sme,
            'airtel_g' =>  $airtel_g,

            'glo_cg' =>  $glo_cg,
            'glo_cg_bal' => number_format($glo_cg_bal, 2),
            'glo_sme_bal' => number_format($glo_sme_bal, 2),
            'glo_g_bal' => number_format($glo_g_bal, 2),
            'glo_sme' =>  $glo_sme,
            'glo_g' =>  $glo_g,

            'mobile_cg' =>  $mobile_cg,
            'mobile_cg_bal' => number_format($mobile_cg_bal, 2),
            'mobile_sme_bal' => number_format($mobile_sme_bal, 2),
            'mobile_g_bal' => number_format($mobile_g_bal, 2),
            'mobile_sme' =>  $mobile_sme,
            'mobile_g' =>  $mobile_g,

            // Airtime summaries
            'mtn_vtu' => number_format($mtn_vtu, 2),
            'mtn_vtu_d' => number_format($mtn_vtu_d, 2),
            'mtn_sns' => number_format($mtn_sns, 2),
            'mtn_sns_d' => number_format($mtn_sns_d, 2),

            'airtel_vtu' => number_format($airtel_vtu, 2),
            'airtel_vtu_d' => number_format($airtel_vtu_d, 2),
            'airtel_sns' => number_format($airtel_sns, 2),
            'airtel_sns_d' => number_format($airtel_sns_d, 2),

            'glo_vtu' => number_format($glo_vtu, 2),
            'glo_vtu_d' => number_format($glo_vtu_d, 2),
            'glo_sns' => number_format($glo_sns, 2),
            'glo_sns_d' => number_format($glo_sns_d, 2),

            'mobile_vtu' => number_format($mobile_vtu, 2),
            'mobile_vtu_d' => number_format($mobile_vtu_d, 2),
            'mobile_sns' => number_format($mobile_sns, 2),
            'mobile_sns_d' => number_format($mobile_sns_d, 2),

            // Cable summaries
            'dstv' => number_format($dstv, 2),
            'dstv_c' => number_format($dstv_c, 2),
            'gotv' => number_format($gotv, 2),
            'gotv_c' => number_format($gotv_c, 2),
            'startime' => number_format($startime, 2),
            'startime_c' => number_format($startime_c, 2),

            // Exam summaries
            'waec' => number_format($waec, 2),
            'waec_q' => number_format($waec_q),
            'neco' => number_format($neco, 2),
            'neco_q' => number_format($neco_q),
            'nabteb' => number_format($nabteb, 2),
            'nabteb_q' => number_format($nabteb_q),

             // Recharge Card summaries
            'mtn_recharge_amount' => number_format($mtn_recharge_amount, 2),
            'mtn_recharge_quantity' => number_format($mtn_recharge_quantity),
            'airtel_recharge_amount' => number_format($airtel_recharge_amount, 2),
            'airtel_recharge_quantity' => number_format($airtel_recharge_quantity),
            'glo_recharge_amount' => number_format($glo_recharge_amount, 2),
            'glo_recharge_quantity' => number_format($glo_recharge_quantity),
            'mobile_recharge_amount' => number_format($mobile_recharge_amount, 2),
            'mobile_recharge_quantity' => number_format($mobile_recharge_quantity),

            // Data Card summaries
            'mtn_datacard_amount' => number_format($mtn_datacard_amount, 2),
            'mtn_datacard_quantity' => number_format($mtn_datacard_quantity),
            'airtel_datacard_amount' => number_format($airtel_datacard_amount, 2),
            'airtel_datacard_quantity' => number_format($airtel_datacard_quantity),
            'glo_datacard_amount' => number_format($glo_datacard_amount, 2),
            'glo_datacard_quantity' => number_format($glo_datacard_quantity),
            'mobile_datacard_amount' => number_format($mobile_datacard_amount, 2),
            'mobile_datacard_quantity' => number_format($mobile_datacard_quantity),

            // Bulk SMS summary
            'bulksms' => number_format($bulksms, 2),

            // Bill payment summary
            'bill' => number_format($bill, 2),

            // Airtime to cash summaries
            'cash_amount' => number_format($cash_amount, 2),
            'cash_pay' => number_format($cash_pay, 2),

            // Deposit and spending summaries
            'deposit_amount' => number_format($deposit_amount, 2),
            'deposit_charges' => number_format($deposit_charges, 2),
            'deposit_trans' => number_format($adex_in_trans_percent, 1),
            'spend_trans' => number_format($adex_out_trans_percent, 1),
            'spend_amount' => number_format($money_spent, 2)
        ]);
    }
    private function calculateDataInGB(string $planName): float
    {
        $planName = strtoupper(trim($planName));
        $gb = 0.0;

        if (str_contains($planName, 'MB')) {
            $mb = (float)str_ireplace('MB', '', $planName);
            $gb = $mb / 1000;
        } elseif (str_contains($planName, 'GB')) {
            $gb = (float)str_ireplace('GB', '', $planName);
        } elseif (str_contains($planName, 'TB')) {
            $tb = (float)str_ireplace('TB', '', $planName);
            $gb = $tb * 1000;
        }

        return $gb;
    }
    private function formatDataVolume(float $volumeInGB): string
    {
        if ($volumeInGB >= 1000) {
            return number_format($volumeInGB / 1000) . 'TB';
        } else {
            return number_format($volumeInGB) . 'GB';
        }
    }
}
