<?php

namespace App\Filament\Admin\Resources\CircleSupervisorResource\Pages;

use App\Filament\Admin\Resources\CircleSupervisorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCircleSupervisors extends ListRecords
{
    protected static string $resource = CircleSupervisorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
