<?php

namespace App\Filament\Admin\Resources\TaskCommentResource\Pages;

use App\Filament\Admin\Resources\TaskCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTaskComment extends CreateRecord
{
    protected static string $resource = TaskCommentResource::class;
}
