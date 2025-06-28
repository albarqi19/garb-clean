<?php

namespace App\Filament\Admin\Resources\KpiValueResource\Pages;

use App\Filament\Admin\Resources\KpiValueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKpiValues extends ListRecords
{
    protected static string $resource = KpiValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
