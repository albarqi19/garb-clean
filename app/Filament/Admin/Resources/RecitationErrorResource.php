<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RecitationErrorResource\Pages;
use App\Filament\Admin\Resources\RecitationErrorResource\RelationManagers;
use App\Models\RecitationError;
use App\Models\RecitationSession;
use App\Services\QuranService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecitationErrorResource extends Resource
{
    protected static ?string $model = RecitationError::class;

    // تعيين أيقونة مناسبة لأخطاء التسميع
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    
    // تعيين العنوان بالعربية
    protected static ?string $label = 'خطأ تسميع';
    protected static ?string $pluralLabel = 'أخطاء التسميع';
    
    // تعيين المجموعة في التنقل
    protected static ?string $navigationGroup = 'إدارة التعليم';
    
    // ترتيب في التنقل
    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ربط الخطأ بالجلسة')
                    ->schema([
                        Forms\Components\Select::make('recitation_session_id')
                            ->label('جلسة التسميع')
                            ->relationship('recitationSession', 'id')
                            ->getOptionLabelFromRecordUsing(fn (RecitationSession $record): string => 
                                sprintf('%s - %s (%s)', 
                                    $record->student->name, 
                                    $record->quran_range, 
                                    $record->created_at->format('Y-m-d')
                                )
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn (): ?int => request()->get('recitation_session_id')),
                    ]),

                Forms\Components\Section::make('موقع الخطأ في القرآن')
                    ->schema([
                        Forms\Components\Select::make('surah_number')
                            ->label('السورة')
                            ->options(function () {
                                return QuranService::getSurahOptions();
                            })
                            ->searchable()
                            ->required(),
                        
                        Forms\Components\TextInput::make('verse_number')
                            ->label('رقم الآية')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        
                        Forms\Components\TextInput::make('word_text')
                            ->label('الكلمة التي بها الخطأ')
                            ->required()
                            ->maxLength(255),
                    ])->columns(3),

                Forms\Components\Section::make('تفاصيل الخطأ')
                    ->schema([
                        Forms\Components\Select::make('error_type')
                            ->label('نوع الخطأ')
                            ->options([
                                'نطق' => 'خطأ نطق',
                                'تجويد' => 'خطأ تجويد',
                                'حفظ' => 'خطأ حفظ',
                                'ترتيل' => 'خطأ ترتيل',
                                'وقف وابتداء' => 'خطأ وقف وابتداء',
                                'أخرى' => 'أخطاء أخرى',
                            ])
                            ->required(),
                        
                        Forms\Components\Select::make('severity_level')
                            ->label('مستوى الخطأ')
                            ->options([
                                'خفيف' => 'خفيف',
                                'متوسط' => 'متوسط',
                                'شديد' => 'شديد',
                            ])
                            ->default('متوسط')
                            ->required(),
                        
                        Forms\Components\Toggle::make('is_repeated')
                            ->label('خطأ متكرر')
                            ->helperText('هل هذا الخطأ متكرر للطالب؟'),
                    ])->columns(3),

                Forms\Components\Section::make('ملاحظات التصحيح')
                    ->schema([
                        Forms\Components\Textarea::make('correction_note')
                            ->label('ملاحظة التصحيح')
                            ->rows(3)
                            ->helperText('كيف تم تصحيح الخطأ')
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('teacher_note')
                            ->label('ملاحظة المعلم')
                            ->rows(3)
                            ->helperText('ملاحظات إضافية من المعلم')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('recitationSession.student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('recitationSession.teacher.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('surah_name')
                    ->label('السورة')
                    ->getStateUsing(function (RecitationError $record): string {
                        return (new QuranService())->getSurahName($record->surah_number);
                    })
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('verse_number')
                    ->label('الآية')
                    ->alignCenter()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('word_text')
                    ->label('الكلمة')
                    ->weight('bold')
                    ->color('danger')
                    ->searchable(),
                
                Tables\Columns\BadgeColumn::make('error_type')
                    ->label('نوع الخطأ')
                    ->colors([
                        'danger' => 'نطق',
                        'warning' => 'تجويد',
                        'info' => 'حفظ',
                        'success' => 'ترتيل',
                        'secondary' => 'وقف وابتداء',
                        'gray' => 'أخرى',
                    ]),
                
                Tables\Columns\BadgeColumn::make('severity_level')
                    ->label('مستوى الخطأ')
                    ->colors([
                        'success' => 'خفيف',
                        'warning' => 'متوسط',
                        'danger' => 'شديد',
                    ]),
                
                Tables\Columns\IconColumn::make('is_repeated')
                    ->label('متكرر')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-path')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning'),
                
                Tables\Columns\TextColumn::make('location_text')
                    ->label('الموقع')
                    ->getStateUsing(function (RecitationError $record): string {
                        $surahName = (new QuranService())->getSurahName($record->surah_number);
                        return sprintf('سورة %s - آية %d', $surahName, $record->verse_number);
                    }),
                
                Tables\Columns\TextColumn::make('recitationSession.created_at')
                    ->label('تاريخ الجلسة')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('error_type')
                    ->label('نوع الخطأ')
                    ->options([
                        'نطق' => 'خطأ نطق',
                        'تجويد' => 'خطأ تجويد',
                        'حفظ' => 'خطأ حفظ',
                        'ترتيل' => 'خطأ ترتيل',
                        'وقف وابتداء' => 'خطأ وقف وابتداء',
                        'أخرى' => 'أخطاء أخرى',
                    ]),
                
                Tables\Filters\SelectFilter::make('severity_level')
                    ->label('مستوى الخطأ')
                    ->options([
                        'خفيف' => 'خفيف',
                        'متوسط' => 'متوسط',
                        'شديد' => 'شديد',
                    ]),
                
                Tables\Filters\Filter::make('is_repeated')
                    ->label('الأخطاء المتكررة')
                    ->query(fn (Builder $query): Builder => $query->where('is_repeated', true)),
                
                Tables\Filters\SelectFilter::make('surah_number')
                    ->label('السورة')
                    ->options(function () {
                        return QuranService::getSurahOptions();
                    })
                    ->searchable(),
                
                Tables\Filters\Filter::make('session_date')
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
                                fn (Builder $query, $date): Builder => $query->whereHas('recitationSession', 
                                    fn (Builder $query) => $query->whereDate('created_at', '>=', $date)
                                ),
                            )
                            ->when(
                                $data['session_until'],
                                fn (Builder $query, $date): Builder => $query->whereHas('recitationSession', 
                                    fn (Builder $query) => $query->whereDate('created_at', '<=', $date)
                                ),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\Action::make('view_session')
                    ->label('عرض الجلسة')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (RecitationError $record): string => 
                        route('filament.admin.resources.recitation-sessions.edit', $record->recitation_session_id)
                    ),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecitationErrors::route('/'),
            'create' => Pages\CreateRecitationError::route('/create'),
            'edit' => Pages\EditRecitationError::route('/{record}/edit'),
        ];
    }
}
