<?php

namespace App\Filament\Admin\Resources\StoreRechargeCardResource\Pages;

use App\Filament\Admin\Resources\StoreRechargeCardResource;
use App\Models\RechargeCardPlan;
use App\Models\StoreRechargeCard;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreateStoreRechargeCard extends CreateRecord
{
    protected static string $resource = StoreRechargeCardResource::class;

    protected function handleRecordCreation(array $data): StoreRechargeCard
    {
        $pins = preg_split('/\r\n|\r|\n/', trim($data['bulk_pins']));
        $serials = preg_split('/\r\n|\r|\n/', trim($data['bulk_serials']));

        if (count($pins) !== count($serials)) {
            Notification::make()
                ->title('Mismatch Detected')
                ->body('Number of PINs (' . count($pins) . ') does not match number of serials (' . count($serials) . ')')
                ->danger()
                ->send();
            throw new Halt();
        }
        $plan = RechargeCardPlan::where('id', $data['recharge_card_id'])->first();
        $name = "{$plan->name}";
        $insertedCount = 0;
        foreach ($pins as $i => $pin) {
            $CPin = trim($pin);
            $exists = StoreRechargeCard::where('pin', $CPin)->where('network', $data['network'])->exists();
            if ($exists) {
                continue;
            }
            StoreRechargeCard::create([
                'network' => $data['network'],
                'pin' => trim($pin),
                'serial' => trim($serials[$i]),
                'plan_status' => $data['plan_status'] ?? false,
                'added_date' => now(),
                'name' => $name,
                'added_by' => auth()->user()?->username,
                'recharge_card_id' =>  $data['recharge_card_id']
            ]);
            $insertedCount++;
        }
        if ($insertedCount === 0) {
            Notification::make()
                ->title('No New Records')
                ->body('All PINs and Serials already exist. Nothing was imported.')
                ->danger()
                ->send();
            throw new Halt();
        }
        Notification::make()
            ->title('Success')
            ->body(count($pins) . ' PINs successfully stored.')
            ->success()
            ->send();
        return StoreRechargeCard::latest()->first();
    }
}
