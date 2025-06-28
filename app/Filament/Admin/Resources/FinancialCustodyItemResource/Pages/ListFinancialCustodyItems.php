<?php

namespace App\Filament\Admin\Resources\FinancialCustodyItemResource\Pages;

use App\Filament\Admin\Resources\FinancialCustodyItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinancialCustodyItems extends ListRecords
{
    protected static string $resource = FinancialCustodyItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
