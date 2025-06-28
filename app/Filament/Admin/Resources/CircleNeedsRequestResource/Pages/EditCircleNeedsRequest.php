<?php

namespace App\Filament\Admin\Resources\CircleNeedsRequestResource\Pages;

use App\Filament\Admin\Resources\CircleNeedsRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCircleNeedsRequest extends EditRecord
{
    protected static string $resource = CircleNeedsRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
