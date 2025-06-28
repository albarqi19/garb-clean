<?php

namespace App\Filament\Admin\Resources\AcademicCalendarResource\Pages;

use App\Filament\Admin\Resources\AcademicCalendarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademicCalendars extends ListRecords
{
    protected static string $resource = AcademicCalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
