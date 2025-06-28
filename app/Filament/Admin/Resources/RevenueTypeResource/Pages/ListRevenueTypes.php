<?php

namespace App\Filament\Admin\Resources\RevenueTypeResource\Pages;

use App\Filament\Admin\Resources\RevenueTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRevenueTypes extends ListRecords
{
    protected static string $resource = RevenueTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
