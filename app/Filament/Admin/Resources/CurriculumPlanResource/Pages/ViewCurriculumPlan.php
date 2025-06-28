<?php

namespace App\Filament\Admin\Resources\CurriculumPlanResource\Pages;

use App\Filament\Admin\Resources\CurriculumPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\KeyValueEntry;

class ViewCurriculumPlan extends ViewRecord
{
    protected static string $resource = CurriculumPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('duplicate')
                ->label('نسخ الخطة')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->action(function () {
                    $record = $this->record;
                    $newPlan = $record->replicate();
                    $newPlan->name = $record->name . ' - نسخة';
                    $newPlan->is_active = false;
                    $newPlan->save();
                    
                    // نسخ أيام الخطة
                    foreach ($record->planDays as $day) {
                        $newDay = $day->replicate();
                        $newDay->curriculum_plan_id = $newPlan->id;
                        $newDay->save();
                    }
                    
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $newPlan]));
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('معلومات الخطة')
                    ->schema([
                        TextEntry::make('name')
                            ->label('اسم الخطة'),
                        TextEntry::make('description')
                            ->label('الوصف'),
                        TextEntry::make('type')
                            ->label('نوع الخطة')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'ثلاثي' => 'success',
                                'ثنائي' => 'info',
                                'حفظ فقط' => 'warning',
                                'مراجعة فقط' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('total_days')
                            ->label('إجمالي الأيام')
                            ->suffix(' يوم'),
                        TextEntry::make('is_active')
                            ->label('الحالة')
                            ->getStateUsing(fn ($record) => $record->is_active ? 'نشطة' : 'غير نشطة')
                            ->badge()
                            ->color(fn ($record) => $record->is_active ? 'success' : 'gray'),
                        TextEntry::make('creator.name')
                            ->label('منشئ الخطة'),
                    ])
                    ->columns(2),
                    
                Section::make('أيام الخطة')
                    ->schema([
                        KeyValueEntry::make('plan_days_summary')
                            ->label('ملخص الأيام')
                            ->getStateUsing(function ($record) {
                                $summary = [];
                                foreach ($record->planDays as $day) {
                                    $activities = [];
                                    if ($day->memorization_enabled) {
                                        $activities[] = 'حفظ: ' . $day->memorization_description;
                                    }
                                    if ($day->minor_review_enabled) {
                                        $activities[] = 'مراجعة صغرى: ' . $day->minor_review_description;
                                    }
                                    if ($day->major_review_enabled) {
                                        $activities[] = 'مراجعة كبرى: ' . $day->major_review_description;
                                    }
                                    
                                    $summary["اليوم {$day->day_number}"] = implode(' | ', $activities) ?: 'لا يوجد أنشطة';
                                }
                                return $summary;
                            })
                            ->columnSpanFull(),
                    ]),
                    
                Section::make('معلومات إضافية')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('تاريخ التحديث')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
