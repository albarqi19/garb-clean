<?php

namespace App\Filament\Admin\Resources\BranchFollowerResource\Pages;

use App\Filament\Admin\Resources\BranchFollowerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBranchFollower extends EditRecord
{
    protected static string $resource = BranchFollowerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
