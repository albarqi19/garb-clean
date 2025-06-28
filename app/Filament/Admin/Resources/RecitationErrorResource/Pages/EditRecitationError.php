<?php

namespace App\Filament\Admin\Resources\RecitationErrorResource\Pages;

use App\Filament\Admin\Resources\RecitationErrorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecitationError extends EditRecord
{
    protected static string $resource = RecitationErrorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
