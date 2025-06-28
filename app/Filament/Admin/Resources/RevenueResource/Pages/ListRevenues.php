<?php

namespace App\Filament\Admin\Resources\RevenueResource\Pages;

use App\Filament\Admin\Resources\RevenueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRevenues extends ListRecords
{
    protected static string $resource = RevenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
