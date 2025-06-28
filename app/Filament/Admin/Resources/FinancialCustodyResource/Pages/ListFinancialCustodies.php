<?php

namespace App\Filament\Admin\Resources\FinancialCustodyResource\Pages;

use App\Filament\Admin\Resources\FinancialCustodyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinancialCustodies extends ListRecords
{
    protected static string $resource = FinancialCustodyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
