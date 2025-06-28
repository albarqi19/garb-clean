<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RecitationSessionResource\Pages;
use App\Filament\Admin\Resources\RecitationSessionResource\RelationManagers;
use App\Models\RecitationSession;
use App\Models\Student;
use App\Models\User;
use App\Models\QuranCircle;
use App\Services\QuranService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecitationSessionResource extends Resource
{
    protected static ?string $model = RecitationSession::class;

    // تعيين أيقونة مناسبة لجلسات التسميع
    protected static ?string $navigationIcon = 'heroicon-o-microphone';
    
    // تعيين العنوان بالعربية
    protected static ?string $modelLabel = 'جلسة تسميع';
    protected static ?string $pluralModelLabel = 'جلسات التسميع';
    protected static ?string $navigationLabel = 'جلسات التسميع';
    
    // تعيين المجموعة في التنقل
    protected static ?string $navigationGroup = 'إدارة التعليم';
    
    // ترتيب في التنقل
    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الجلسة الأساسية')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->label('الطالب')
                            ->relationship('student', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        
                        Forms\Components\Select::make('teacher_id')
                            ->label('المعلم')
                            ->relationship('teacher', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                          Forms\Components\Select::make('circle_id')
                            ->label('الحلقة القرآنية')
                            ->relationship('circle', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                          
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('تاريخ ووقت الجلسة')
                            ->required()
                            ->default(now()),
                            
                        Forms\Components\Select::make('recitation_type')
                            ->label('نوع التسميع')
                            ->options([
                                'حفظ' => 'حفظ جديد',
                                'مراجعة صغرى' => 'مراجعة صغرى', 
                                'مراجعة كبرى' => 'مراجعة كبرى',
                                'تثبيت' => 'تثبيت',
                            ])
                            ->required()
                            ->default('حفظ'),
                            
                        Forms\Components\Select::make('status')
                            ->label('حالة الجلسة')
                            ->options([
                                'جارية' => 'جارية',
                                'مكتملة' => 'مكتملة',
                                'غير مكتملة' => 'غير مكتملة',
                            ])
                            ->required()
                            ->default('جارية')
                            ->live()
                            ->helperText('تحديد حالة الجلسة الحالية'),
                            
                        Forms\Components\Select::make('curriculum_id')
                            ->label('المنهج الدراسي')
                            ->relationship('curriculum', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('اختيار المنهج (اختياري)')
                            ->helperText('ربط الجلسة بمنهج دراسي محدد'),
                    ])->columns(2),Forms\Components\Section::make('النطاق القرآني')
                    ->schema([
                        Forms\Components\Select::make('start_surah_number')
                            ->label('السورة الأولى')
                            ->options([
                                1 => '1 - الفاتحة',
                                2 => '2 - البقرة',
                                3 => '3 - آل عمران',
                                4 => '4 - النساء',
                                5 => '5 - المائدة',
                                6 => '6 - الأنعام',
                                7 => '7 - الأعراف',
                                8 => '8 - الأنفال',
                                9 => '9 - التوبة',
                                10 => '10 - يونس',
                                11 => '11 - هود',
                                12 => '12 - يوسف',
                                13 => '13 - الرعد',
                                14 => '14 - إبراهيم',
                                15 => '15 - الحجر',
                                // يمكن إضافة باقي السور هنا
                            ])
                            ->searchable()
                            ->required(),
                        
                        Forms\Components\TextInput::make('start_verse')
                            ->label('الآية الأولى')
                            ->numeric()
                            ->required()
                            ->default(1),
                        
                        Forms\Components\Select::make('end_surah_number')
                            ->label('السورة الأخيرة')
                            ->options([
                                1 => '1 - الفاتحة',
                                2 => '2 - البقرة',
                                3 => '3 - آل عمران',
                                4 => '4 - النساء',
                                5 => '5 - المائدة',
                                6 => '6 - الأنعام',
                                7 => '7 - الأعراف',
                                8 => '8 - الأنفال',
                                9 => '9 - التوبة',
                                10 => '10 - يونس',
                                11 => '11 - هود',
                                12 => '12 - يوسف',
                                13 => '13 - الرعد',
                                14 => '14 - إبراهيم',
                                15 => '15 - الحجر',
                                // يمكن إضافة باقي السور هنا
                            ])
                            ->searchable()
                            ->required(),
                        
                        Forms\Components\TextInput::make('end_verse')
                            ->label('الآية الأخيرة')
                            ->numeric()
                            ->required()
                            ->default(1),
                    ])->columns(4),                Forms\Components\Section::make('التقييم')
                    ->schema([
                        Forms\Components\TextInput::make('grade')
                            ->label('الدرجة')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(10)
                            ->required()
                            ->suffix('/ 10')
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state !== null) {
                                    $grade = (float) $state;
                                    if ($grade >= 9.0) $evaluation = 'ممتاز';
                                    elseif ($grade >= 8.0) $evaluation = 'جيد جداً';
                                    elseif ($grade >= 7.0) $evaluation = 'جيد';
                                    elseif ($grade >= 6.0) $evaluation = 'مقبول';
                                    else $evaluation = 'ضعيف';
                                    
                                    $set('evaluation', $evaluation);
                                }
                            }),
                        
                        Forms\Components\Select::make('evaluation')
                            ->label('التقدير')
                            ->options([
                                'ممتاز' => 'ممتاز',
                                'جيد جداً' => 'جيد جداً',
                                'جيد' => 'جيد',
                                'مقبول' => 'مقبول',
                                'ضعيف' => 'ضعيف',
                            ])
                            ->required(),
                          Forms\Components\TextInput::make('duration_minutes')
                            ->label('مدة الجلسة (بالدقائق)')
                            ->numeric()
                            ->suffix('دقيقة')
                            ->default(15),
                    ])->columns(3),

                Forms\Components\Section::make('حالة الجلسة والمنهج')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('حالة الجلسة')
                            ->options([
                                'جارية' => 'جارية',
                                'مكتملة' => 'مكتملة',
                                'غير مكتملة' => 'غير مكتملة',
                            ])
                            ->default('جارية')
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        Forms\Components\Select::make('curriculum_id')
                            ->label('المنهج الدراسي')
                            ->relationship('curriculum', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('الملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('teacher_notes')
                            ->label('ملاحظات المعلم')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),]);
    }

    public static function table(Table $table): Table
    {
        return $table            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الجلسة')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('circle.name')
                    ->label('الحلقة')
                    ->searchable()
                    ->sortable(),
                  Tables\Columns\TextColumn::make('recitation_type')
                    ->label('نوع التسميع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'حفظ' => 'success',
                        'مراجعة صغرى' => 'info',
                        'مراجعة كبرى' => 'warning',
                        'تثبيت' => 'gray',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('حالة الجلسة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'مكتملة' => 'success',
                        'جارية' => 'warning',
                        'غير مكتملة' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'مكتملة' => 'heroicon-o-check-circle',
                        'جارية' => 'heroicon-o-clock',
                        'غير مكتملة' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    }),
                
                Tables\Columns\TextColumn::make('curriculum.name')
                    ->label('المنهج')
                    ->placeholder('غير محدد')
                    ->toggleable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('grade')
                    ->label('الدرجة')
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 9.0 => 'success',
                        $state >= 8.0 => 'info',
                        $state >= 7.0 => 'warning',
                        $state >= 6.0 => 'gray',
                        default => 'danger',
                    })
                    ->sortable(),
                  Tables\Columns\TextColumn::make('evaluation')
                    ->label('التقدير')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ممتاز' => 'success',
                        'جيد جداً' => 'info',
                        'جيد' => 'warning',
                        'مقبول' => 'gray',
                        'ضعيف' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('has_errors')
                    ->label('يحتوي أخطاء')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
                
                Tables\Columns\TextColumn::make('errors_count')
                    ->label('عدد الأخطاء')
                    ->getStateUsing(fn ($record) => $record->errors()->count())
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'success',
                        $state <= 3 => 'warning',
                        default => 'danger',
                    }),
                
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('المدة')
                    ->suffix(' دقيقة')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('student_id')
                    ->label('الطالب')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('المعلم')
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->preload(),
                  Tables\Filters\SelectFilter::make('quran_circle_id')
                    ->label('الحلقة')
                    ->relationship('circle', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('recitation_type')
                    ->label('نوع التسميع')
                    ->options([
                        'حفظ' => 'حفظ جديد',
                        'مراجعة صغرى' => 'مراجعة صغرى',
                        'مراجعة كبرى' => 'مراجعة كبرى',
                        'تثبيت' => 'تثبيت',
                    ]),
                
                Tables\Filters\SelectFilter::make('evaluation')
                    ->label('التقدير')
                    ->options([
                        'ممتاز' => 'ممتاز',
                        'جيد جداً' => 'جيد جداً',
                        'جيد' => 'جيد',
                        'مقبول' => 'مقبول',
                        'ضعيف' => 'ضعيف',
                    ]),
                  Tables\Filters\SelectFilter::make('status')
                    ->label('حالة الجلسة')
                    ->options([
                        'جارية' => 'جارية',
                        'مكتملة' => 'مكتملة',
                        'غير مكتملة' => 'غير مكتملة',
                    ])
                    ->native(false),
                
                Tables\Filters\SelectFilter::make('curriculum_id')
                    ->label('المنهج')
                    ->relationship('curriculum', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('session_from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('session_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['session_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['session_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                
                Tables\Filters\Filter::make('high_grades')
                    ->label('درجات عالية (8+)')
                    ->query(fn (Builder $query): Builder => $query->where('grade', '>=', 8)),
                
                Tables\Filters\Filter::make('low_grades')
                    ->label('درجات منخفضة (أقل من 6)')
                    ->query(fn (Builder $query): Builder => $query->where('grade', '<', 6)),
            ])            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ErrorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecitationSessions::route('/'),
            'create' => Pages\CreateRecitationSession::route('/create'),
            'edit' => Pages\EditRecitationSession::route('/{record}/edit'),
        ];
    }
}
