<?php

namespace App\Filament\Admin\Resources\StudentCurriculumResource\Pages;

use App\Filament\Admin\Resources\StudentCurriculumResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentCurriculum extends EditRecord
{
    protected static string $resource = StudentCurriculumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('viewProgress')
                ->label('عرض التقدم')
                ->icon('heroicon-s-eye')
                ->url(fn () => $this->getResource()::getUrl('progress', ['record' => $this->record])),
        ];
    }
}
