<?php

namespace App\Filament\Admin\Resources\StoreDataCardPlanResource\Pages;

use App\Filament\Admin\Resources\StoreDataCardPlanResource;
use App\Models\DataCardPlan;
use App\Models\StoreDataCard;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreateStoreDataCardPlan extends CreateRecord
{
    protected static string $resource = StoreDataCardPlanResource::class;

    protected function handleRecordCreation(array $data): StoreDataCard
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
        $plan = DataCardPlan::where('id', $data['data_card_id'])->first();
        $name = "{$plan->name} | {$plan->plan_type} | {$plan->plan_day}";
        $insertedCount = 0;
        foreach ($pins as $i => $pin) {
            $CPin = trim($pin);
            $exists = StoreDataCard::where('pin', $CPin)->where('network', $data['network'])->exists();
            if ($exists) {
                continue;
            }
            StoreDataCard::create([
                'network' => $data['network'],
                'pin' => trim($pin),
                'serial' => trim($serials[$i]),
                'plan_status' => $data['plan_status'] ?? false,
                'added_date' => now(),
                'name' => $name,
                'added_by' => auth()->user()?->username,
                'data_card_id' =>  $data['data_card_id']
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
        return StoreDataCard::latest()->first();
    }
}
