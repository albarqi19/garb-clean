<?php

namespace App\Filament\Admin\Resources\SalaryResource\Pages;

use App\Filament\Admin\Resources\SalaryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalary extends EditRecord
{
    protected static string $resource = SalaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
