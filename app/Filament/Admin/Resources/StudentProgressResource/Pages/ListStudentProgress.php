<?php

namespace App\Filament\Admin\Resources\StudentProgressResource\Pages;

use App\Filament\Admin\Resources\StudentProgressResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentProgress extends ListRecords
{
    protected static string $resource = StudentProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
