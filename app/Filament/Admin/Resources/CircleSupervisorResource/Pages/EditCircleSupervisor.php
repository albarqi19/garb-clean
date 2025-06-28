<?php

namespace App\Filament\Admin\Resources\CircleSupervisorResource\Pages;

use App\Filament\Admin\Resources\CircleSupervisorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCircleSupervisor extends EditRecord
{
    protected static string $resource = CircleSupervisorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
