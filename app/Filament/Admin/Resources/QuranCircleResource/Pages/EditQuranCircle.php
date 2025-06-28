<?php

namespace App\Filament\Admin\Resources\QuranCircleResource\Pages;

use App\Filament\Admin\Resources\QuranCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuranCircle extends EditRecord
{
    protected static string $resource = QuranCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
