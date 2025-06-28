<?php

namespace App\Filament\Admin\Resources\CircleIncentiveResource\Pages;

use App\Filament\Admin\Resources\CircleIncentiveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCircleIncentive extends EditRecord
{
    protected static string $resource = CircleIncentiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
