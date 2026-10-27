<?php

namespace App\Filament\Admin\Resources\StorePinResource\Pages;

use App\Filament\Admin\Resources\StorePinResource;
use App\Models\StockResultPin;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreateStorePin extends CreateRecord
{
    protected static string $resource = StorePinResource::class;

    protected function handleRecordCreation(array $data): StockResultPin
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
        $lastPlan = StockResultPin::orderByDesc('id')->first();
        $nextPlanId = (string) ($lastPlan ? ((int) $lastPlan->plan_id + 1) : 1);
        $insertedCount = 0;
        $lastInserted = null;
        foreach ($pins as $i => $pin) {
            $examPin = trim($pin);
            $exists = StockResultPin::where('exam_pin', $examPin)->where('exam_name', $data['exam_name'])->exists();
            if ($exists) {
                continue;
            }
            StockResultPin::create([
                'exam_name' => $data['exam_name'],
                'exam_pin' => trim($pin),
                'exam_serial' => trim($serials[$i]),
                'plan_status' => $data['plan_status'] ?? false,
                'added_date' => now(),
                'added_by' => auth()->user()?->username,
                'plan_id' => (string) $nextPlanId++
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
        return StockResultPin::latest()->first();
    }
}
