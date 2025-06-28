<?php

namespace App\Filament\Admin\Resources\QuranCircleResource\Pages;

use App\Filament\Admin\Resources\QuranCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuranCircles extends ListRecords
{
    protected static string $resource = QuranCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
