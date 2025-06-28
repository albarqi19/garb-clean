<?php

namespace App\Filament\Admin\Resources\StudentCurriculumResource\Pages;

use App\Filament\Admin\Resources\StudentCurriculumResource;
use App\Models\CurriculumPlan;
use App\Models\StudentCurriculumProgress;
use App\Models\StudentCurriculum;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use App\Filament\Components\ProgressColumn;

class ViewStudentCurriculumProgress extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = StudentCurriculumResource::class;
    
    protected static ?string $title = 'تقدم الطالب في المنهج';
    
    protected static string $view = 'filament.admin.resources.student-curriculum-resource.pages.view-student-curriculum-progress';
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Infolists\Components\Section::make('تفاصيل المنهج')
                    ->schema([
                        Infolists\Components\TextEntry::make('student.name')
                            ->label('الطالب'),
                        Infolists\Components\TextEntry::make('curriculum.name')
                            ->label('المنهج'),
                        Infolists\Components\TextEntry::make('curriculum.type')
                            ->label('نوع المنهج'),
                        Infolists\Components\TextEntry::make('level.name')
                            ->label('المستوى')
                            ->visible(fn() => $this->record->curriculum->type === 'منهج طالب'),
                        Infolists\Components\TextEntry::make('teacher.name')
                            ->label('المعلم المشرف'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('الحالة'),
                        Infolists\Components\TextEntry::make('completion_percentage')
                            ->label('نسبة الإكمال')
                            ->suffix('%')
                            ->formatStateUsing(fn ($state) => number_format($state)),
                        Infolists\Components\TextEntry::make('start_date')
                            ->label('تاريخ البدء')
                            ->date('Y-m-d'),
                        Infolists\Components\TextEntry::make('completion_date')
                            ->label('تاريخ الإكمال')
                            ->date('Y-m-d'),
                    ])
                    ->columns(2),
            ]);
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(StudentCurriculumProgress::query()->where('student_curriculum_id', $this->record->id))
            ->columns([
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('اسم الخطة')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plan.plan_type')
                    ->label('نوع الخطة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'الدرس' => 'primary',
                        'المراجعة الصغرى' => 'warning',
                        'المراجعة الكبرى' => 'success',
                        default => 'gray',                    }),
                \App\Filament\Components\ProgressColumn::make('completion_percentage')
                    ->label('نسبة الإكمال'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'قيد التنفيذ' => 'info',
                        'مكتمل' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البدء')
                    ->date('Y-m-d'),
                Tables\Columns\TextColumn::make('completion_date')
                    ->label('تاريخ الإكمال')
                    ->date('Y-m-d'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan_type')
                    ->label('نوع الخطة')
                    ->options([
                        'الدرس' => 'الدرس',
                        'المراجعة الصغرى' => 'المراجعة الصغرى',
                        'المراجعة الكبرى' => 'المراجعة الكبرى',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'قيد التنفيذ' => 'قيد التنفيذ',
                        'مكتمل' => 'مكتمل',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('updateProgress')
                    ->label('تحديث التقدم')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\TextInput::make('completion_percentage')
                            ->label('نسبة الإكمال')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required(),
                        Forms\Components\Textarea::make('teacher_notes')
                            ->label('ملاحظات المعلم'),
                        Forms\Components\Toggle::make('mark_complete')
                            ->label('تعليم كمكتمل')
                            ->helperText('إذا تم تحديد هذا الخيار، سيتم تعليم الخطة كمكتملة ووضع نسبة الإكمال على 100%'),
                    ])
                    ->action(function (array $data, StudentCurriculumProgress $record): void {
                        if ($data['mark_complete']) {
                            $record->markAsCompleted();
                        } else {
                            $record->updateProgress($data['completion_percentage']);
                        }
                        
                        $record->teacher_notes = $data['teacher_notes'];
                        $record->save();
                        
                        $this->record->refresh();
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('addProgress')
                    ->label('إضافة خطة جديدة')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\Select::make('curriculum_plan_id')
                            ->label('خطة المنهج')
                            ->options(function () {
                                return CurriculumPlan::where('curriculum_id', $this->record->curriculum_id)
                                    ->when($this->record->curriculum->type === 'منهج طالب' && $this->record->curriculum_level_id, function ($query) {
                                        $query->where('curriculum_level_id', $this->record->curriculum_level_id);
                                    })
                                    ->where('is_active', true)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required(),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ البدء')
                            ->default(now())
                            ->required(),
                        Forms\Components\Textarea::make('teacher_notes')
                            ->label('ملاحظات المعلم'),
                    ])
                    ->action(function (array $data): void {
                        StudentCurriculumProgress::create([
                            'student_curriculum_id' => $this->record->id,
                            'curriculum_plan_id' => $data['curriculum_plan_id'],
                            'start_date' => $data['start_date'],
                            'status' => 'قيد التنفيذ',
                            'completion_percentage' => 0,
                            'teacher_notes' => $data['teacher_notes'],
                        ]);
                        
                        $this->record->refresh();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('العودة')
                ->url(static::getResource()::getUrl())
                ->icon('heroicon-s-arrow-left'),
            Actions\EditAction::make()
                ->record($this->record),
        ];
    }
}