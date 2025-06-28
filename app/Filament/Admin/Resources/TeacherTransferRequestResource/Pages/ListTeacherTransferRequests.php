<?php

namespace App\Filament\Admin\Resources\TeacherTransferRequestResource\Pages;

use App\Filament\Admin\Resources\TeacherTransferRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherTransferRequests extends ListRecords
{
    protected static string $resource = TeacherTransferRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
