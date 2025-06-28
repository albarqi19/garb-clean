<?php

namespace App\Filament\Admin\Resources\RecitationSessionResource\Pages;

use App\Filament\Admin\Resources\RecitationSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecitationSessions extends ListRecords
{
    protected static string $resource = RecitationSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة جلسة تسميع'),
        ];
    }

    public function getTitle(): string
    {
        return 'جلسات التسميع';
    }

    protected function getHeaderTitle(): string
    {
        return 'جلسات التسميع';
    }
}
