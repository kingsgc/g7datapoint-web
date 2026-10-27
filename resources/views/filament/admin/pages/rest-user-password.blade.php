<x-filament-panels::page>
    <x-filament-panels::form wire:submit="resetPassword">
        {{ $this->form }}

        {{ $this->getFormActions }}
    </x-filament-panels::form>
</x-filament-panels::page>
