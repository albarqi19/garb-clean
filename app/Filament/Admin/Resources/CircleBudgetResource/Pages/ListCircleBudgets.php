<?php

namespace App\Filament\Admin\Resources\CircleBudgetResource\Pages;

use App\Filament\Admin\Resources\CircleBudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCircleBudgets extends ListRecords
{
    protected static string $resource = CircleBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
