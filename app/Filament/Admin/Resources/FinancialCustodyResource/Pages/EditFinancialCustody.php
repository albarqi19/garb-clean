<?php

namespace App\Filament\Admin\Resources\FinancialCustodyResource\Pages;

use App\Filament\Admin\Resources\FinancialCustodyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFinancialCustody extends EditRecord
{
    protected static string $resource = FinancialCustodyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
