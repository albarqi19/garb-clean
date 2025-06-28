<?php

namespace App\Filament\Admin\Resources\CircleIncentiveResource\Pages;

use App\Filament\Admin\Resources\CircleIncentiveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCircleIncentives extends ListRecords
{
    protected static string $resource = CircleIncentiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
