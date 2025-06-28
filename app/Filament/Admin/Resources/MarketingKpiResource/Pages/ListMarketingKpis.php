<?php

namespace App\Filament\Admin\Resources\MarketingKpiResource\Pages;

use App\Filament\Admin\Resources\MarketingKpiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMarketingKpis extends ListRecords
{
    protected static string $resource = MarketingKpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
