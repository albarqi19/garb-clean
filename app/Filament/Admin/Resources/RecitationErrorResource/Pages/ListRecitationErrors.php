<?php

namespace App\Filament\Admin\Resources\RecitationErrorResource\Pages;

use App\Filament\Admin\Resources\RecitationErrorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecitationErrors extends ListRecords
{
    protected static string $resource = RecitationErrorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
