<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StudentProgressResource\Pages;
use App\Filament\Admin\Resources\StudentProgressResource\RelationManagers;
use App\Models\StudentProgress;
use App\Models\Student;
use App\Models\Curriculum;
use App\Models\CurriculumPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class StudentProgressResource extends Resource
{
    protected static ?string $model = StudentProgress::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    
    protected static ?string $navigationLabel = 'تقدم الطلاب';
    
    protected static ?string $modelLabel = 'تقدم طالب';
    
    protected static ?string $pluralModelLabel = 'تقدم الطلاب';
      protected static ?string $navigationGroup = 'إدارة الطلاب والمعلمين';
    
    protected static ?int $navigationSort = 23;public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات أساسية')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->label('الطالب')
                            ->relationship('student', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),
                            
                        Forms\Components\Select::make('curriculum_id')
                            ->label('المنهج')
                            ->relationship('curriculum', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->columnSpan(1),
                            
                        Forms\Components\Select::make('curriculum_plan_id')
                            ->label('خطة المنهج')
                            ->relationship('curriculumPlan', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('حالة التقدم')
                    ->schema([
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
                            ->columnSpan(1),
                            
                        Forms\Components\Select::make('recitation_status')
                            ->label('حالة التسميع')
                            ->options([
                                'pending' => 'في انتظار التسميع',
                                'passed' => 'نجح في التسميع',
                                'failed' => 'رسب في التسميع',
                                'partial' => 'تسميع جزئي',
                                'excellent' => 'ممتاز',
                            ])
                            ->columnSpan(1),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('التقييم والأداء')
                    ->schema([
                        Forms\Components\TextInput::make('performance_score')
                            ->label('درجة الأداء')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.1)
                            ->suffix('من 10')
                            ->columnSpan(1),
                            
                        Forms\Components\TextInput::make('recitation_attempts')
                            ->label('عدد محاولات التسميع')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->columnSpan(1),
                            
                        Forms\Components\TextInput::make('memorized_verses')
                            ->label('الآيات المحفوظة')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->columnSpan(1),
                            
                        Forms\Components\TextInput::make('memorization_accuracy')
                            ->label('دقة الحفظ %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->columnSpan(1),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('التوقيت')
                    ->schema([
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('تاريخ البداية')
                            ->columnSpan(1),
                            
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('تاريخ الإكمال')
                            ->columnSpan(1),
                            
                        Forms\Components\DateTimePicker::make('last_recitation_at')
                            ->label('آخر تسميع')
                            ->columnSpan(1),
                            
                        Forms\Components\TextInput::make('time_spent_minutes')
                            ->label('الوقت المستغرق (دقيقة)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('دقيقة')
                            ->columnSpan(1),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('الملاحظات والتقييم')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات عامة')
                            ->rows(3)
                            ->columnSpanFull(),
                            
                        Forms\Components\Textarea::make('teacher_feedback')
                            ->label('تقييم المعلم')
                            ->rows(3)
                            ->columnSpanFull(),
                            
                        Forms\Components\Select::make('evaluated_by')
                            ->label('تم التقييم بواسطة')
                            ->relationship('evaluator', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                    ]),
            ]);
    }    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),
                    
                Tables\Columns\TextColumn::make('curriculum.name')
                    ->label('المنهج')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                    
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
                    
                Tables\Columns\BadgeColumn::make('recitation_status')
                    ->label('التسميع')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'في الانتظار',
                        'passed' => 'نجح',
                        'failed' => 'رسب',
                        'partial' => 'جزئي',
                        'excellent' => 'ممتاز',
                        default => 'لم يحدد',
                    })
                    ->colors([
                        'secondary' => 'pending',
                        'success' => 'passed',
                        'danger' => 'failed',
                        'warning' => 'partial',
                        'success' => 'excellent',
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
                        
                        // Get total verses based on plan type
                        $total = 0;
                        if ($record->curriculumPlan) {
                            if ($record->curriculumPlan->range_type === 'multi_surah') {
                                $total = $record->curriculumPlan->total_verses_calculated ?? 0;
                            } else {
                                $total = $record->curriculumPlan->calculated_verses ?? 0;
                            }
                        }
                        
                        return $total > 0 ? "{$memorized}/{$total}" : (string)$memorized;
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('memorization_accuracy')
                    ->label('دقة الحفظ')
                    ->formatStateUsing(fn (?float $state): string => $state ? number_format($state, 1) . '%' : '-')
                    ->color(fn (?float $state): string => match (true) {
                        $state === null => 'secondary',
                        $state >= 90 => 'success',
                        $state >= 70 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('recitation_attempts')
                    ->label('المحاولات')
                    ->sortable()
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('last_recitation_at')
                    ->label('آخر تسميع')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('time_spent_minutes')
                    ->label('الوقت المستغرق')
                    ->formatStateUsing(fn (int $state): string => $state . ' د')
                    ->sortable()
                    ->toggleable(),
                    
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
                    
                SelectFilter::make('recitation_status')
                    ->label('حالة التسميع')
                    ->options([
                        'pending' => 'في انتظار التسميع',
                        'passed' => 'نجح في التسميع',
                        'failed' => 'رسب في التسميع',
                        'partial' => 'تسميع جزئي',
                        'excellent' => 'ممتاز',
                    ]),
                    
                SelectFilter::make('curriculum_id')
                    ->label('المنهج')
                    ->relationship('curriculum', 'name'),
                    
                Tables\Filters\Filter::make('needs_review')
                    ->label('يحتاج مراجعة')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'needs_revision'))
                    ->toggle(),
                    
                Tables\Filters\Filter::make('low_performance')
                    ->label('أداء ضعيف')
                    ->query(fn (Builder $query): Builder => $query->where('performance_score', '<', 6))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('record_recitation')
                    ->label('تسجيل تسميع')
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
                    
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الطالب والمنهج')
                    ->schema([
                        Infolists\Components\TextEntry::make('student.name')
                            ->label('الطالب')
                            ->weight(FontWeight::Bold),
                        Infolists\Components\TextEntry::make('curriculum.name')
                            ->label('المنهج'),
                        Infolists\Components\TextEntry::make('curriculumPlan.display_content')
                            ->label('المحتوى')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Infolists\Components\Section::make('حالة التقدم')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
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
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'not_started' => 'secondary',
                                'in_progress' => 'warning',
                                'completed' => 'success',
                                'reviewed' => 'primary',
                                'mastered' => 'success',
                                'needs_revision' => 'danger',
                                default => 'secondary',
                            }),
                            
                        Infolists\Components\TextEntry::make('recitation_status')
                            ->label('حالة التسميع')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'pending' => 'في الانتظار',
                                'passed' => 'نجح',
                                'failed' => 'رسب',
                                'partial' => 'جزئي',
                                'excellent' => 'ممتاز',
                                default => 'لم يحدد',
                            })
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'pending' => 'secondary',
                                'passed' => 'success',
                                'failed' => 'danger',
                                'partial' => 'warning',
                                'excellent' => 'success',
                                default => 'secondary',
                            }),
                    ])
                    ->columns(2),
                    
                Infolists\Components\Section::make('إحصائيات الأداء')
                    ->schema([
                        Infolists\Components\TextEntry::make('performance_score')
                            ->label('درجة الأداء')
                            ->formatStateUsing(fn (?float $state): string => $state ? number_format($state, 1) . '/10' : 'غير مقيم')
                            ->color(fn (?float $state): string => match (true) {
                                $state === null => 'secondary',
                                $state >= 8 => 'success',
                                $state >= 6 => 'warning',
                                default => 'danger',
                            }),
                            
                        Infolists\Components\TextEntry::make('memorization_accuracy')
                            ->label('دقة الحفظ')
                            ->formatStateUsing(fn (?float $state): string => $state ? number_format($state, 1) . '%' : 'غير محدد')
                            ->color(fn (?float $state): string => match (true) {
                                $state === null => 'secondary',
                                $state >= 90 => 'success',
                                $state >= 70 => 'warning',
                                default => 'danger',
                            }),
                              Infolists\Components\TextEntry::make('memorized_verses')
                            ->label('الآيات المحفوظة')
                            ->formatStateUsing(function ($record) {
                                $memorized = $record->memorized_verses ?? 0;
                                
                                // Get total verses based on plan type
                                $total = 0;
                                if ($record->curriculumPlan) {
                                    if ($record->curriculumPlan->range_type === 'multi_surah') {
                                        $total = $record->curriculumPlan->total_verses_calculated ?? 0;
                                    } else {
                                        $total = $record->curriculumPlan->calculated_verses ?? 0;
                                    }
                                }
                                
                                if ($total > 0) {
                                    $percentage = round(($memorized / $total) * 100, 1);
                                    return "{$memorized}/{$total} ({$percentage}%)";
                                }
                                return (string)$memorized;
                            }),
                            
                        Infolists\Components\TextEntry::make('recitation_attempts')
                            ->label('محاولات التسميع'),
                            
                        Infolists\Components\TextEntry::make('time_spent_minutes')
                            ->label('الوقت المستغرق')
                            ->formatStateUsing(function (int $state): string {
                                $hours = intval($state / 60);
                                $minutes = $state % 60;
                                return $hours > 0 ? "{$hours}س {$minutes}د" : "{$minutes}د";
                            }),
                    ])
                    ->columns(3),
                    
                Infolists\Components\Section::make('التوقيت')
                    ->schema([
                        Infolists\Components\TextEntry::make('started_at')
                            ->label('تاريخ البداية')
                            ->dateTime('Y-m-d H:i'),
                            
                        Infolists\Components\TextEntry::make('completed_at')
                            ->label('تاريخ الإكمال')
                            ->dateTime('Y-m-d H:i'),
                            
                        Infolists\Components\TextEntry::make('last_recitation_at')
                            ->label('آخر تسميع')
                            ->dateTime('Y-m-d H:i'),
                    ])
                    ->columns(3),
                    
                Infolists\Components\Section::make('الملاحظات والتقييم')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('ملاحظات عامة')
                            ->columnSpanFull(),
                            
                        Infolists\Components\TextEntry::make('teacher_feedback')
                            ->label('تقييم المعلم')
                            ->columnSpanFull(),
                            
                        Infolists\Components\TextEntry::make('evaluator.name')
                            ->label('تم التقييم بواسطة'),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentProgress::route('/'),
            'create' => Pages\CreateStudentProgress::route('/create'),
            'view' => Pages\ViewStudentProgress::route('/{record}'),
            'edit' => Pages\EditStudentProgress::route('/{record}/edit'),
        ];
    }
}
