<?php

namespace App\Filament\Admin\Resources\CircleBudgetResource\Pages;

use App\Filament\Admin\Resources\CircleBudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCircleBudget extends EditRecord
{
    protected static string $resource = CircleBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
