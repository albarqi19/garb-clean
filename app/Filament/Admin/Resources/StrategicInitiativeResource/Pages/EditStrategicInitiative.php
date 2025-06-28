<?php

namespace App\Filament\Admin\Resources\StrategicInitiativeResource\Pages;

use App\Filament\Admin\Resources\StrategicInitiativeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStrategicInitiative extends EditRecord
{
    protected static string $resource = StrategicInitiativeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
