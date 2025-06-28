<?php

namespace App\Filament\Admin\Resources\MarketingTaskWeekResource\Pages;

use App\Filament\Admin\Resources\MarketingTaskWeekResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMarketingTaskWeeks extends ListRecords
{
    protected static string $resource = MarketingTaskWeekResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
