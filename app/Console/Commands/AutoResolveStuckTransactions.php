<?php

namespace App\Console\Commands;

use App\Models\Airtime;
use App\Models\Bill;
use App\Models\Cable;
use App\Models\Data;
use App\Models\Exam;
use App\Models\History;
use App\Models\User;
use App\Models\WalletFunding;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoResolveStuckTransactions extends Command
{
    protected $signature = 'transactions:auto-resolve {--minutes=20 : Minutes before a processing transaction is considered stuck}';
    protected $description = 'Auto-fail transactions stuck in processing state due to API timeout or missing response';

    /**
     * Transaction types to monitor, mapped to their model class and role name in the history table.
     */
    private array $transactionTypes = [
        ['model' => Data::class,    'role' => 'data'],
        ['model' => Airtime::class, 'role' => 'airtime'],
        ['model' => Cable::class,   'role' => 'cable'],
        ['model' => Bill::class,    'role' => 'bill'],
        ['model' => Exam::class,    'role' => 'exam'],
    ];

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $cutoff = Carbon::now()->subMinutes($minutes);
        $totalResolved = 0;

        foreach ($this->transactionTypes as $type) {
            $resolved = $this->resolveForModel($type['model'], $type['role'], $cutoff);
            $totalResolved += $resolved;
        }

        if ($totalResolved > 0) {
            Log::info("AutoResolveStuckTransactions: resolved {$totalResolved} stuck transaction(s) older than {$minutes} minutes.");
        }

        return self::SUCCESS;
    }

    private function resolveForModel(string $modelClass, string $role, Carbon $cutoff): int
    {
        $resolved = 0;

        $stuck = $modelClass::where('plan_status', 0)
            ->where('updated_at', '<', $cutoff)
            ->get();

        foreach ($stuck as $transaction) {
            try {
                DB::beginTransaction();

                // Re-fetch with lock to prevent double processing
                $transaction = $modelClass::where('id', $transaction->id)
                    ->where('plan_status', 0)
                    ->lockForUpdate()
                    ->first();

                if (!$transaction) {
                    DB::rollBack();
                    continue;
                }

                $this->refundUser($transaction);

                $transaction->update([
                    'plan_status' => 2,
                    'ServerResponse' => ($transaction->ServerResponse ?? '') .
                        ' | Auto-failed after ' . now()->diffInMinutes($transaction->updated_at) . ' mins with no API response.',
                ]);

                History::where('transid', $transaction->transid)
                    ->where('plan_status', 0)
                    ->update([
                        'plan_status' => 2,
                        'message' => 'Auto-failed: no API response received within the allowed time. Amount refunded.',
                    ]);

                DB::commit();
                $resolved++;

                Log::warning("AutoResolveStuckTransactions: auto-failed {$role} transaction #{$transaction->id} (transid: {$transaction->transid}, user: {$transaction->username}).");
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error("AutoResolveStuckTransactions: failed to resolve {$role} transaction #{$transaction->id} — " . $e->getMessage());
            }
        }

        return $resolved;
    }

    private function refundUser($transaction): void
    {
        $wallet = strtolower($transaction->wallet ?? 'wallet');

        if ($wallet === 'wallet' || empty($wallet)) {
            // Refund to main wallet
            User::where('id', $transaction->user_id)
                ->lockForUpdate()
                ->increment('bal', $transaction->amount);
        } else {
            // Refund to stock/vending wallet — map 9mobile → mobile for the balance key
            $balanceKey = str_replace('9mobile', 'mobile', $wallet) . '_bal';

            $walletStore = WalletFunding::where('user_id', $transaction->user_id)
                ->lockForUpdate()
                ->first();

            if ($walletStore && array_key_exists($balanceKey, $walletStore->toArray())) {
                $walletStore->increment($balanceKey, $transaction->amount);
            } else {
                // Fallback: refund to main wallet if vending wallet not found
                User::where('id', $transaction->user_id)
                    ->lockForUpdate()
                    ->increment('bal', $transaction->amount);
                Log::warning("AutoResolveStuckTransactions: vending wallet key '{$balanceKey}' not found for user {$transaction->user_id}, refunded to main wallet.");
            }
        }
    }
}
