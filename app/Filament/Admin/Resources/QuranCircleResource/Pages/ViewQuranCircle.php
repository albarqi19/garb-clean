<?php

namespace App\Filament\Admin\Resources\QuranCircleResource\Pages;

use App\Filament\Admin\Resources\QuranCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Models\Teacher;

class ViewQuranCircle extends ViewRecord
{
    protected static string $resource = QuranCircleResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('add_teacher')
                ->label('إضافة معلم جديد')
                ->icon('heroicon-o-academic-cap')
                ->color('primary')
                ->url(function ($record) {
                    return route('filament.admin.resources.teachers.create', [
                        'quran_circle_id' => $record->id,
                        'mosque_id' => $record->mosque_id,
                    ]);
                })
                ->openUrlInNewTab(),
                
            Actions\Action::make('add_circle_group')
                ->label('إضافة حلقة فرعية')
                ->icon('heroicon-o-user-group')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('اسم الحلقة')
                        ->required(),
                    \Filament\Forms\Components\Select::make('teacher_id')
                        ->label('المعلم')
                        ->options(function ($livewire) {
                            $record = $livewire->getRecord();
                            $options = [];
                            
                            // 1. المعلمين المكلفين في هذه الحلقة (النظام الجديد)
                            $assignedTeachers = $record->activeTeachers;
                            foreach ($assignedTeachers as $teacher) {
                                $options[$teacher->id] = $teacher->name . ' (مكلف)';
                            }
                            
                            // 2. معلمي نفس المسجد
                            if ($record->mosque_id) {
                                $mosqueTeachers = Teacher::where('mosque_id', $record->mosque_id)->get();
                                foreach ($mosqueTeachers as $teacher) {
                                    if (!isset($options[$teacher->id])) {
                                        $options[$teacher->id] = $teacher->name;
                                    }
                                }
                            }
                            
                            // 3. جميع المعلمين كخيار أخير
                            if (empty($options)) {
                                $allTeachers = Teacher::all();
                                foreach ($allTeachers as $teacher) {
                                    $options[$teacher->id] = $teacher->name;
                                }
                            }
                            
                            return $options;
                        })
                        ->searchable()
                        ->helperText('يتم عرض المعلمين المكلفين أولاً، ثم معلمي نفس المسجد')
                        ->required(),
                    \Filament\Forms\Components\Select::make('status')
                        ->label('حالة الحلقة')
                        ->options([
                            'نشطة' => 'نشطة',
                            'معلقة' => 'معلقة',
                            'مغلقة' => 'مغلقة',
                        ])
                        ->default('نشطة'),
                    \Filament\Forms\Components\Textarea::make('description')
                        ->label('وصف الحلقة')
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $record = $this->getRecord();
                    
                    // تحقق ما إذا كانت المدرسة من نوع حلقة جماعية
                    if ($record->circle_type !== 'حلقة جماعية') {
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('لا يمكن إضافة حلقة فرعية')
                            ->body('يمكن إضافة الحلقات الفرعية فقط للمدارس القرآنية من نوع حلقة جماعية.')
                            ->send();
                        return;
                    }
                    
                    // إنشاء حلقة فرعية جديدة
                    $record->circleGroups()->create([
                        'name' => $data['name'],
                        'teacher_id' => $data['teacher_id'],
                        'status' => $data['status'],
                        'description' => $data['description'] ?? null,
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('تم إضافة الحلقة الفرعية بنجاح')
                        ->send();
                        
                    // إعادة تحميل الصفحة
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $record]));
                })
                ->visible(fn ($record) => $record->circle_type === 'حلقة جماعية'),
            
            Actions\EditAction::make(),
        ];
    }
}
