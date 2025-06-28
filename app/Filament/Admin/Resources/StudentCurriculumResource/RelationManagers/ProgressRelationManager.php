<?php

namespace App\Filament\Admin\Resources\StudentCurriculumResource\RelationManagers;

use App\Models\CurriculumPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Components\ProgressColumn;

class ProgressRelationManager extends RelationManager
{
    protected static string $relationship = 'progress';
    
    protected static ?string $title = 'تقدم الطالب في خطط المنهج';
    protected static ?string $label = 'تقدم';
    protected static ?string $pluralLabel = 'التقدم';

    public function form(Form $form): Form
    {
        $curriculumId = $this->ownerRecord->curriculum_id;
        $levelId = $this->ownerRecord->curriculum_level_id;
        $curriculumType = $this->ownerRecord->curriculum->type;
        
        return $form
            ->schema([
                Forms\Components\Select::make('curriculum_plan_id')
                    ->label('خطة المنهج')
                    ->options(
                        CurriculumPlan::where('curriculum_id', $curriculumId)
                            ->when($curriculumType === 'منهج طالب' && $levelId, function ($query) use ($levelId) {
                                $query->where('curriculum_level_id', $levelId);
                            })
                            ->where('is_active', true)
                            ->pluck('name', 'id')
                    )
                    ->required()
                    ->searchable(),
                Forms\Components\DatePicker::make('start_date')
                    ->label('تاريخ البدء')
                    ->required()
                    ->default(now()),
                Forms\Components\DatePicker::make('completion_date')
                    ->label('تاريخ الإكمال')
                    ->nullable(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'قيد التنفيذ' => 'قيد التنفيذ',
                        'مكتمل' => 'مكتمل',
                    ])
                    ->required()
                    ->default('قيد التنفيذ'),
                Forms\Components\TextInput::make('completion_percentage')
                    ->label('نسبة الإكمال')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->default(0)
                    ->required(),
                Forms\Components\Textarea::make('teacher_notes')
                    ->label('ملاحظات المعلم')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
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
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'قيد التنفيذ' => 'قيد التنفيذ',
                        'مكتمل' => 'مكتمل',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function () {
                        // تحديث نسبة إكمال المنهج بعد إضافة خطة جديدة
                        $this->ownerRecord->updateCompletionPercentage();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function () {
                        // تحديث نسبة إكمال المنهج بعد تحديث الخطة
                        $this->ownerRecord->updateCompletionPercentage();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        // تحديث نسبة إكمال المنهج بعد حذف خطة
                        $this->ownerRecord->updateCompletionPercentage();
                    }),
                Tables\Actions\Action::make('markAsCompleted')
                    ->label('تعليم كمكتمل')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status !== 'مكتمل')
                    ->action(function ($record) {
                        $record->markAsCompleted();
                        $this->ownerRecord->refresh();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            // تحديث نسبة إكمال المنهج بعد حذف عدة خطط
                            $this->ownerRecord->updateCompletionPercentage();
                        }),
                ]),
            ]);
    }
}
