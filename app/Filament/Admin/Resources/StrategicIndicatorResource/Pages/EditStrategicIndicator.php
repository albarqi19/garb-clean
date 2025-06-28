<?php

namespace App\Filament\Admin\Resources\StrategicIndicatorResource\Pages;

use App\Filament\Admin\Resources\StrategicIndicatorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStrategicIndicator extends EditRecord
{
    protected static string $resource = StrategicIndicatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
