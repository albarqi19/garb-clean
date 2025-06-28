<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TeacherEvaluationResource\Pages;
use App\Filament\Admin\Resources\TeacherEvaluationResource\RelationManagers;
use App\Models\TeacherEvaluation;
use App\Models\Teacher;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;

class TeacherEvaluationResource extends Resource
{
    protected static ?string $model = TeacherEvaluation::class;

    // تخصيص المورد بالعربية
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $label = 'تقييم معلم';
    protected static ?string $pluralLabel = 'تقييمات المعلمين';
    protected static ?string $navigationGroup = 'التعليمية';
    protected static ?int $navigationSort = 25;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('بيانات المعلم والتقييم')
                    ->description('اختيار المعلم وبيانات التقييم الأساسية')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Select::make('teacher_id')
                            ->label('المعلم')
                            ->relationship('teacher', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn (Teacher $record) => "{$record->name} - {$record->mosque?->name}")
                            ->helperText('اختر المعلم المراد تقييمه'),

                        Forms\Components\DatePicker::make('evaluation_date')
                            ->label('تاريخ التقييم')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),

                        Forms\Components\Select::make('evaluation_period')
                            ->label('فترة التقييم')
                            ->options([
                                'شهري' => 'تقييم شهري',
                                'فصلي' => 'تقييم فصلي',
                                'نصف سنوي' => 'تقييم نصف سنوي',
                                'سنوي' => 'تقييم سنوي',
                                'تقييم خاص' => 'تقييم خاص',
                            ])
                            ->required()
                            ->default('شهري'),

                        Forms\Components\Select::make('status')
                            ->label('حالة التقييم')
                            ->options([
                                'مسودة' => 'مسودة',
                                'مكتمل' => 'مكتمل',
                                'معتمد' => 'معتمد',
                                'مراجعة' => 'قيد المراجعة',
                            ])
                            ->default('مسودة')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('معايير التقييم التفصيلية')
                    ->description('تقييم المعلم وفقاً للمعايير المحددة (كل معيار من 20 نقطة)')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('performance_score')
                                    ->label('تقييم الأداء')
                                    ->helperText('جودة التدريس والالتزام بالمنهج')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(20)
                                    ->step(0.5)
                                    ->suffix('/ 20')
                                    ->default(0)
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set, callable $get) => 
                                        self::updateTotalScore($set, $get)),

                                Forms\Components\TextInput::make('attendance_score')
                                    ->label('تقييم الالتزام بالحضور')
                                    ->helperText('انتظام الحضور والالتزام بالمواعيد')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(20)
                                    ->step(0.5)
                                    ->suffix('/ 20')
                                    ->default(0)
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set, callable $get) => 
                                        self::updateTotalScore($set, $get)),

                                Forms\Components\TextInput::make('student_interaction_score')
                                    ->label('تقييم التفاعل مع الطلاب')
                                    ->helperText('التواصل مع الطلاب وحل مشاكلهم')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(20)
                                    ->step(0.5)
                                    ->suffix('/ 20')
                                    ->default(0)
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set, callable $get) => 
                                        self::updateTotalScore($set, $get)),

                                Forms\Components\TextInput::make('behavior_cooperation_score')
                                    ->label('تقييم السمت والتعاون')
                                    ->helperText('الأخلاق والتعامل مع الزملاء')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(20)
                                    ->step(0.5)
                                    ->suffix('/ 20')
                                    ->default(0)
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set, callable $get) => 
                                        self::updateTotalScore($set, $get)),

                                Forms\Components\TextInput::make('memorization_recitation_score')
                                    ->label('تقييم الحفظ والتلاوة')
                                    ->helperText('إتقان القرآن وجودة التلاوة')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(20)
                                    ->step(0.5)
                                    ->suffix('/ 20')
                                    ->default(0)
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set, callable $get) => 
                                        self::updateTotalScore($set, $get)),

                                Forms\Components\TextInput::make('general_evaluation_score')
                                    ->label('التقييم العام')
                                    ->helperText('التقييم الشامل للأداء العام')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(20)
                                    ->step(0.5)
                                    ->suffix('/ 20')
                                    ->default(0)
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set, callable $get) => 
                                        self::updateTotalScore($set, $get)),
                            ]),

                        Forms\Components\TextInput::make('total_score')
                            ->label('النتيجة الإجمالية')
                            ->suffix('/ 100')
                            ->disabled()
                            ->default(0)
                            ->extraAttributes(['class' => 'font-bold text-lg'])
                            ->helperText('يتم حسابها تلقائياً'),
                    ]),

                Section::make('بيانات المقيم والملاحظات')
                    ->description('بيانات من قام بالتقييم والملاحظات الإضافية')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Forms\Components\Select::make('evaluator_id')
                            ->label('المقيم')
                            ->relationship('evaluator', 'name')
                            ->searchable()
                            ->preload()
                            ->default(Auth::id())
                            ->required(),

                        Forms\Components\Select::make('evaluator_role')
                            ->label('صفة المقيم')
                            ->options([
                                'مدير' => 'مدير',
                                'مشرف' => 'مشرف',
                                'مشرف تربوي' => 'مشرف تربوي',
                                'معلم أول' => 'معلم أول',
                                'أخرى' => 'أخرى',
                            ])
                            ->default('مشرف')
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات التقييم')
                            ->rows(4)
                            ->columnSpanFull()
                            ->placeholder('اكتب ملاحظاتك حول أداء المعلم...'),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * دالة لحساب النتيجة الإجمالية تلقائياً
     */
    private static function updateTotalScore(callable $set, callable $get): void
    {
        $total = ($get('performance_score') ?? 0) +
                 ($get('attendance_score') ?? 0) +
                 ($get('student_interaction_score') ?? 0) +
                 ($get('behavior_cooperation_score') ?? 0) +
                 ($get('memorization_recitation_score') ?? 0) +
                 ($get('general_evaluation_score') ?? 0);

        $set('total_score', $total);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('teacher.mosque.name')
                    ->label('المسجد')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('evaluation_date')
                    ->label('تاريخ التقييم')
                    ->date('d-m-Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('evaluation_period')
                    ->label('فترة التقييم')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'شهري' => 'info',
                        'فصلي' => 'primary',
                        'نصف سنوي' => 'warning',
                        'سنوي' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_score')
                    ->label('النتيجة الإجمالية')
                    ->suffix('%')
                    ->sortable()
                    ->color(fn ($state) => match (true) {
                        $state >= 90 => 'success',
                        $state >= 80 => 'primary',
                        $state >= 70 => 'info',
                        $state >= 60 => 'warning',
                        default => 'danger',
                    })
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('performance_grade')
                    ->label('التصنيف')
                    ->badge()
                    ->color(fn (TeacherEvaluation $record) => $record->grade_color),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'مسودة' => 'gray',
                        'مكتمل' => 'warning',
                        'معتمد' => 'success',
                        'مراجعة' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('evaluator.name')
                    ->label('المقيم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('evaluation_date', 'desc')
            ->filters([
                SelectFilter::make('teacher_id')
                    ->label('المعلم')
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('evaluation_period')
                    ->label('فترة التقييم')
                    ->options([
                        'شهري' => 'تقييم شهري',
                        'فصلي' => 'تقييم فصلي',
                        'نصف سنوي' => 'تقييم نصف سنوي',
                        'سنوي' => 'تقييم سنوي',
                        'تقييم خاص' => 'تقييم خاص',
                    ]),

                SelectFilter::make('status')
                    ->label('حالة التقييم')
                    ->options([
                        'مسودة' => 'مسودة',
                        'مكتمل' => 'مكتمل',
                        'معتمد' => 'معتمد',
                        'مراجعة' => 'قيد المراجعة',
                    ]),

                Tables\Filters\Filter::make('total_score')
                    ->label('النتيجة الإجمالية')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('score_from')
                                    ->label('من')
                                    ->numeric()
                                    ->placeholder('0'),
                                Forms\Components\TextInput::make('score_to')
                                    ->label('إلى')
                                    ->numeric()
                                    ->placeholder('100'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['score_from'],
                                fn (Builder $query, $score): Builder => $query->where('total_score', '>=', $score),
                            )
                            ->when(
                                $data['score_to'],
                                fn (Builder $query, $score): Builder => $query->where('total_score', '<=', $score),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                
                Action::make('approve')
                    ->label('اعتماد')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (TeacherEvaluation $record): bool => $record->status !== 'معتمد')
                    ->requiresConfirmation()
                    ->modalHeading('اعتماد التقييم')
                    ->modalDescription(fn (TeacherEvaluation $record): string => 
                        "هل تريد اعتماد تقييم المعلم {$record->teacher->name}؟")
                    ->action(fn (TeacherEvaluation $record) => $record->update(['status' => 'معتمد'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                    
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('اعتماد المحدد')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status' => 'معتمد'])))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
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
            'index' => Pages\ListTeacherEvaluations::route('/'),
            'create' => Pages\CreateTeacherEvaluation::route('/create'),
            'edit' => Pages\EditTeacherEvaluation::route('/{record}/edit'),
        ];
    }

    /**
     * شارة العدد في القائمة
     */
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'مراجعة')->count() ?: null;
    }

    /**
     * لون شارة العدد
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
