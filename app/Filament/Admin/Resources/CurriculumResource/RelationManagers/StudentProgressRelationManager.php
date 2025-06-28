<?php

namespace App\Filament\Admin\Resources\CurriculumResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\StudentProgress;
use Filament\Tables\Filters\SelectFilter;
use Filament\Support\Enums\FontWeight;

class StudentProgressRelationManager extends RelationManager
{
    protected static string $relationship = 'studentProgress';
    
    protected static ?string $title = 'تقدم الطلاب';
    
    protected static ?string $modelLabel = 'تقدم طالب';
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->label('الطالب')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                    
                Forms\Components\Select::make('curriculum_plan_id')
                    ->label('خطة المنهج')
                    ->relationship('curriculumPlan', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                    
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'not_started' => 'لم يبدأ',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'reviewed' => 'تم المراجعة',
                        'mastered' => 'متقن',
                        'needs_revision' => 'يحتاج مراجعة',
                    ])
                    ->required()
                    ->default('not_started'),
                    
                Forms\Components\TextInput::make('performance_score')
                    ->label('درجة الأداء')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10)
                    ->step(0.1),
                    
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('student.name')
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),
                    
                Tables\Columns\TextColumn::make('curriculumPlan.display_content')
                    ->label('المحتوى')
                    ->limit(40)
                    ->tooltip(function ($record) {
                        return $record->curriculumPlan?->display_content;
                    }),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'not_started' => 'لم يبدأ',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'reviewed' => 'تم المراجعة',
                        'mastered' => 'متقن',
                        'needs_revision' => 'يحتاج مراجعة',
                        default => $state,
                    })
                    ->colors([
                        'secondary' => 'not_started',
                        'warning' => 'in_progress',
                        'success' => 'completed',
                        'primary' => 'reviewed',
                        'success' => 'mastered',
                        'danger' => 'needs_revision',
                    ]),
                    
                Tables\Columns\TextColumn::make('performance_score')
                    ->label('الدرجة')
                    ->formatStateUsing(fn (?float $state): string => $state ? number_format($state, 1) . '/10' : '-')
                    ->color(fn (?float $state): string => match (true) {
                        $state === null => 'secondary',
                        $state >= 8 => 'success',
                        $state >= 6 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('memorized_verses')
                    ->label('الآيات المحفوظة')
                    ->formatStateUsing(function ($record) {
                        $memorized = $record->memorized_verses ?? 0;
                        $total = $record->curriculumPlan?->calculated_verses ?? 0;
                        return $total > 0 ? "{$memorized}/{$total}" : (string)$memorized;
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('last_recitation_at')
                    ->label('آخر تسميع')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'not_started' => 'لم يبدأ',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'reviewed' => 'تم المراجعة',
                        'mastered' => 'متقن',
                        'needs_revision' => 'يحتاج مراجعة',
                    ]),
                    
                Tables\Filters\Filter::make('needs_review')
                    ->label('يحتاج مراجعة')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'needs_revision'))
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة طالب'),
            ])
            ->actions([
                Tables\Actions\Action::make('record_recitation')
                    ->label('تسميع')
                    ->icon('heroicon-o-microphone')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('recitation_result')
                            ->label('نتيجة التسميع')
                            ->options([
                                'passed' => 'نجح',
                                'failed' => 'رسب',
                                'partial' => 'جزئي',
                                'excellent' => 'ممتاز',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('score')
                            ->label('الدرجة')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.1),
                        Forms\Components\Textarea::make('feedback')
                            ->label('ملاحظات المعلم')
                            ->rows(3),
                    ])
                    ->action(function (array $data, StudentProgress $record): void {
                        $record->addRecitationAttempt(
                            $data['recitation_result'],
                            $data['score'] ?? null,
                            $data['feedback'] ?? null
                        );
                    }),
                    
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
