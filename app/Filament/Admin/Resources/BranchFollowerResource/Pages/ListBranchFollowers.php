<?php

namespace App\Filament\Admin\Resources\BranchFollowerResource\Pages;

use App\Filament\Admin\Resources\BranchFollowerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBranchFollowers extends ListRecords
{
    protected static string $resource = BranchFollowerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
