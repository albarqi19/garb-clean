<?php

namespace App\Filament\Admin\Resources\TeacherIncentiveResource\Pages;

use App\Filament\Admin\Resources\TeacherIncentiveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherIncentives extends ListRecords
{
    protected static string $resource = TeacherIncentiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
