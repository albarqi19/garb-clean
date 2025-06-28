<?php

namespace App\Filament\Admin\Resources\SalaryRateResource\Pages;

use App\Filament\Admin\Resources\SalaryRateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalaryRates extends ListRecords
{
    protected static string $resource = SalaryRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
