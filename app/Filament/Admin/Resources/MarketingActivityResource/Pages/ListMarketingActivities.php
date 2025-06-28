<?php

namespace App\Filament\Admin\Resources\MarketingActivityResource\Pages;

use App\Filament\Admin\Resources\MarketingActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMarketingActivities extends ListRecords
{
    protected static string $resource = MarketingActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
