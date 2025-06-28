<?php

namespace App\Filament\Admin\Resources\CircleOpeningRequestResource\Pages;

use App\Filament\Admin\Resources\CircleOpeningRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCircleOpeningRequest extends EditRecord
{
    protected static string $resource = CircleOpeningRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
