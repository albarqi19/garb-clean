<?php

namespace App\Filament\Admin\Resources\StudentTransferRequestResource\Pages;

use App\Filament\Admin\Resources\StudentTransferRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentTransferRequests extends ListRecords
{
    protected static string $resource = StudentTransferRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
