<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['password'] = Hash::make($data['password']);
        $data['apikey'] = bin2hex(openssl_random_pseudo_bytes(30));
        $data['bal'] = 0;
        $data['refbal'] = 0;
        $data['date'] = now();
        return $data;
    }
}
