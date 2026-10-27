<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use App\Models\{
    History,
    Data,
    Airtime,
    Cable,
    BankTransfer,
    Bill,
    BulkSms,
    Cash,
    Exam,
    Deposit,
    RechargeCard,
    DumpRechargeCardPin,
    DataCard,
    DumpDataCardPin
};

class Trans extends Controller
{
    /**
     * Generic transaction list loader
     */
    protected function getTransactions(Request $request, $model, array $searchableColumns, $userColumn = 'user_id', $dateFields = ['plan_date', 'date'])
    {
        $user = auth()->user();
        $search = strtolower($request->search ?? '');
        $status = $request->status ?? 'ALL';
        $perPage = $request->adex ?? 15;

        $query = $model::where('user_id', $user->id);
         $table = (new $model)->getTable();
        if ($status !== 'ALL') {
          if(Schema::hasColumn($table, 'plan_status')){
            $query->where('plan_status', $status);
            }else{
            $query->where('status', $status);
            }
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($searchableColumns, $search) {
                foreach ($searchableColumns as $column) {
                    $q->orWhere($column, 'LIKE', "%{$search}%");
                }
            });
        }

        $results = $query->orderByDesc('id')->paginate($perPage);

        $results->getCollection()->transform(function ($item) use ($dateFields) {
            unset(
                $item->vending_system,
                $item->vending_url,
                $item->request_time,
                $item->response_time,
                $item->ServerResponse,
                $item->user_id
            );
            foreach ($dateFields as $field) {
                if (!empty($item->$field)) {
                    $item->$field = Carbon::parse($item->$field)->format('d M Y, h:i A');
                }
            }
            return $item;
        });

        return $results;
    }

    /**
     * Fetch single transaction by my_trans_id or transid
     */
    protected function fetchTransaction($model, $transId, $withExtra = false)
    {
        $table = (new $model)->getTable();

      $trans = $model::query()->when(Schema::hasColumn($table, 'my_trans_id'), function ($query) use ($transId) {
                        $query->where('my_trans_id', $transId)
                              ->orWhere('transid', $transId);
                    }, function ($query) use ($transId) {
                        $query->where('transid', $transId);
                    })->first();

        if (!$trans) {
            return response()->json(['message' => 'Not Available'], 403);
        }

        if (!empty($trans->plan_date)) {
            $trans->plan_date = Carbon::parse($trans->plan_date)->format('d M Y, h:i A');
        }
         if (!empty($trans->date)) {
            $trans->date = Carbon::parse($trans->date)->format('d M Y, h:i A');
        }

        unset(
            $trans->vending_system,
            $trans->vending_url,
            $trans->request_time,
            $trans->response_time,
            $trans->ServerResponse,
            $trans->user_id
        );

        if ($withExtra) {
             $recharge_card_trans = DumpDataCardPin::where('my_trans_id', $trans->my_trans_id)->get();
             $d_card = '';
        foreach ($recharge_card_trans as $pin) {
            $d_card .= '
                <div class="col">
                    <div class="card">
                        <div class="top-logo">
                            <img src="'.config('app.front_end_url').'/upload/'.strtolower($trans->network).'.png" height="40px">
                            <span class="company">'.$trans->card_name.'</span>
                            <span>'.$trans->plan_name.'</span>
                        </div>

                        <div class="price">
                            '.$trans->network.' Data CARD
                        </div>

                        <div class="pin">
                            PIN : '.$pin->pin.' <br>
                            <span> S/N : '.$pin->serial.'</span>
                        </div>
                    </div>
                </div>
            ';
        }
        $html_cont = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Print</title>
            <style>
                html, body {
                    padding: 0;
                    margin: 0;
                    box-sizing: border-box;
                    font-size: 62.5%;
                    font-family: Franklin Gothic Medium, Arial Narrow, Arial, sans-serif;
                }
                .row { display: flex; flex-wrap: wrap; }
                .col { width: 32%; margin: 5px; }
                .top-logo { display: flex; justify-content: space-between; align-items: center; }
                .top-logo span { font-weight: bold; font-size: 20px; }
                .card {
                    padding: 10px;
                    height: 180px;
                    background-color: white;
                    border: 2px dashed orange;
                }
                .price { text-align: center; font-size: 20px; font-weight: bold; }
                .price span { font-size: 12px; font-weight: bold; }
                .text { margin: 2px 0; font-size: 12px; text-align: center; }
                .pin {
                    margin: 10px 0;
                    text-align: center;
                    border: 1px solid rgb(182, 179, 179);
                    padding: 7px;
                    font-size: 20px;
                    border-radius: 10px;
                    font-weight: bolder;
                }
                .pin span { font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="row">
                '.$d_card.'
            </div>
        </body>
        </html>';
            return response()->json([
                'trans'    => $trans,
                'card_map' => $recharge_card_trans,
                'html' => $html_cont
            ]);
        }

        return response()->json(['trans' => $trans]);
    }

    // ================= List Endpoints ================= //

    public function UserTrans(Request $request)
    {
        $tables = [
            'bank_trans' => [BankTransfer::class, ['amount', 'date', 'account_name', 'transid', 'account_number', 'bank_name', 'bank_code']],
            'cable_trans' => [Cable::class, ['amount', 'plan_date', 'charges', 'transid', 'cable_plan', 'cable_name', 'iuc', 'customer_name']],
            'bill_trans' => [Bill::class, ['disco_name', 'plan_date', 'oldbal', 'transid', 'newbal', 'meter_number', 'meter_type', 'customer_name', 'token', 'system']],
            'bulksms_trans' => [BulkSms::class, ['correct_number', 'wrong_number', 'oldbal', 'transid', 'newbal', 'total_correct_number', 'total_wrong_number', 'message', 'sender_name', 'numbers']],
            'cash_trans' => [Cash::class, ['amount', 'amount_credit', 'oldbal', 'transid', 'newbal', 'payment_type', 'network', 'sender_number', 'system', 'username']],
            'result_trans' => [Exam::class, ['amount', 'purchase_code', 'oldbal', 'transid', 'newbal', 'plan_date', 'exam_name', 'quantity', 'username']]
        ];

        $db = strtolower($request->database_name);

        if (!isset($tables[$db])) {
            return response()->json(['message' => 'Invalid request'], 403);
        }

        $results = $this->getTransactions($request, $tables[$db][0], $tables[$db][1]);

        return response()->json([$db => $results]);
    }

    public function AllDepositHistory(Request $request)
    {
        $results = $this->getTransactions(
            $request,
            Deposit::class,
            ['amount', 'date', 'oldbal', 'transid', 'newbal', 'wallet_type', 'type', 'credit_by', 'charges', 'monify_ref'],
            'user_id'
        );

        return response()->json(['deposit_trans' => $results]);
    }

    public function AllHistoryUser(Request $request)
    {
        $results = $this->getTransactions(
            $request,
            History::class,
            ['message', 'adex_date', 'oldbal', 'transid', 'newbal'],
            'user_id',
            ['adex_date']
        );

        return response()->json(['all_summary' => $results]);
    }

    public function AllAirtimeUser(Request $request)
    {
        $results = $this->getTransactions(
            $request,
            Airtime::class,
            ['network', 'network_type', 'oldbal', 'transid', 'newbal', 'plan_date', 'discount', 'plan_phone', 'system']
        );

        return response()->json(['airtime_trans' => $results]);
    }

    public function AllStockHistoryUser(Request $request)
    {
        $results = $this->getTransactions(
            $request,
            Data::class,
            ['network', 'plan_date', 'oldbal', 'newbal', 'transid', 'my_trans_id', 'api_response', 'plan_phone', 'system', 'network_type', 'wallet', 'plan_name']
        );

        return response()->json(['data_trans' => $results]);
    }

    public function AllDataHistoryUser(Request $request)
    {
        $results = $this->getTransactions(
            $request,
            Data::class,
            ['network', 'plan_date', 'oldbal', 'newbal', 'transid', 'my_trans_id', 'api_response', 'plan_phone', 'system', 'network_type', 'wallet', 'plan_name']
        );

        return response()->json(['data_trans' => $results]);
    }

    public function DataRechardPrint(Request $request)
    {
        $tables = [
            'data_card' => [DataCard::class, ['plan_date', 'load_pin', 'transid', 'newbal', 'system', 'plan_type', 'card_name', 'plan_name']],
            'recharge_card' => [RechargeCard::class, ['plan_date', 'load_pin', 'transid', 'newbal', 'system', 'card_name', 'plan_name']]
        ];

        $db = strtolower($request->database_name);

        if (!isset($tables[$db])) {
            return response()->json(['status' => 403, 'message' => 'Not Found'], 403);
        }

        $results = $this->getTransactions($request, $tables[$db][0], $tables[$db][1], 'username');

        return response()->json([$db => $results]);
    }

    // ================= Single Transaction Endpoints ================= //

    public function DataTrans(Request $request) { return $this->fetchTransaction(Data::class, $request->id); }
    public function AirtimeTrans(Request $request) { return $this->fetchTransaction(Airtime::class, $request->id); }
    public function DepositTrans(Request $request) { return $this->fetchTransaction(Deposit::class, $request->id); }
    public function CableTrans(Request $request) { return $this->fetchTransaction(Cable::class, $request->id); }
    public function BillTrans(Request $request) { return $this->fetchTransaction(Bill::class, $request->id); }
    public function AirtimeCashTrans(Request $request) { return $this->fetchTransaction(Cash::class, $request->id); }
    public function BulkSMSTrans(Request $request) { return $this->fetchTransaction(BulkSms::class, $request->id); }
    public function ResultCheckerTrans(Request $request) { return $this->fetchTransaction(Exam::class, $request->id); }
    public function ManualTransfer(Request $request) { return $this->fetchTransaction(BankTransfer::class, $request->id); }
    public function DataCardInvoice(Request $request) { return $this->fetchTransaction(DataCard::class, $request->id); }
    public function DataCardSuccess(Request $request) { return $this->fetchTransaction(DataCard::class, $request->id, true); }

    public function RechargeCardProcess(Request $request)
    {
        return $this->fetchTransaction(RechargeCard::class, $request->id);
    }

public function RechargeCardPrint(Request $request)
{
    $transaction = RechargeCard::where(function ($query) use ($request) {
        $query->orWhere('my_trans_id', $request->id)
              ->orWhere('transid', $request->id);
    })->first();

    if ($transaction) {
        if (!empty($transaction->plan_date)) {
            $transaction->plan_date = Carbon::parse($transaction->plan_date)
                ->format('d M Y, h:i A');
        }

        // Fetch card pins
        $data_card_pin = DumpRechargeCardPin::where('my_trans_id', $transaction->my_trans_id)->get();

        // Build cards HTML
        $d_card = '';
        foreach ($data_card_pin as $pin) {
            $d_card .= '
                <div class="col">
                    <div class="card">
                        <div class="top-logo">
                            <img src="'.config('app.front_end_url').'/upload/'.strtolower($transaction->network).'.png" height="40px">
                            <span class="company">'.$transaction->card_name.'</span>
                            <span>'.$transaction->plan_name.'</span>
                        </div>

                        <div class="price">
                            '.$transaction->network.' Recharge CARD
                        </div>

                        <div class="pin">
                            PIN : '.$pin->pin.' <br>
                            <span> S/N : '.$pin->serial.'</span>
                        </div>
                    </div>
                </div>
            ';
        }
        $html_cont = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Print</title>
            <style>
                html, body {
                    padding: 0;
                    margin: 0;
                    box-sizing: border-box;
                    font-size: 62.5%;
                    font-family: Franklin Gothic Medium, Arial Narrow, Arial, sans-serif;
                }
                .row { display: flex; flex-wrap: wrap; }
                .col { width: 32%; margin: 5px; }
                .top-logo { display: flex; justify-content: space-between; align-items: center; }
                .top-logo span { font-weight: bold; font-size: 20px; }
                .card {
                    padding: 10px;
                    height: 180px;
                    background-color: white;
                    border: 2px dashed orange;
                }
                .price { text-align: center; font-size: 20px; font-weight: bold; }
                .price span { font-size: 12px; font-weight: bold; }
                .text { margin: 2px 0; font-size: 12px; text-align: center; }
                .pin {
                    margin: 10px 0;
                    text-align: center;
                    border: 1px solid rgb(182, 179, 179);
                    padding: 7px;
                    font-size: 20px;
                    border-radius: 10px;
                    font-weight: bolder;
                }
                .pin span { font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="row">
                '.$d_card.'
            </div>
        </body>
        </html>';
        return response()->json([ 'trans' => $transaction, 'card_map' => $data_card_pin, 'html' => $html_cont ]);
    }
        return response()->json(['message' => 'Not Available'], 403);
    }
}
