<?php

namespace App\Filament\Admin\Resources\CircleNeedsRequestResource\Pages;

use App\Filament\Admin\Resources\CircleNeedsRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCircleNeedsRequests extends ListRecords
{
    protected static string $resource = CircleNeedsRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
