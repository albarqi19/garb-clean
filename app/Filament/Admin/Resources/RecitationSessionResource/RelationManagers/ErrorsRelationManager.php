<?php

namespace App\Filament\Admin\Resources\RecitationSessionResource\RelationManagers;

use App\Models\RecitationError;
use App\Services\QuranService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ErrorsRelationManager extends RelationManager
{
    protected static string $relationship = 'errors';

    protected static ?string $title = 'أخطاء التسميع';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('موقع الخطأ في القرآن')
                    ->schema([                        Forms\Components\Select::make('surah_number')
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
                            ->rows(2)
                            ->helperText('كيف تم تصحيح الخطأ'),
                        
                        Forms\Components\Textarea::make('teacher_note')
                            ->label('ملاحظة المعلم')
                            ->rows(2)
                            ->helperText('ملاحظات إضافية من المعلم'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('word_text')
            ->columns([
                Tables\Columns\TextColumn::make('surah_name')
                    ->label('السورة')
                    ->getStateUsing(function (RecitationError $record): string {
                        return (new QuranService())->getSurahName($record->surah_number);
                    }),
                
                Tables\Columns\TextColumn::make('verse_number')
                    ->label('الآية')
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('word_text')
                    ->label('الكلمة')
                    ->weight('bold')
                    ->color('danger'),
                
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
                    ->query(fn ($query) => $query->where('is_repeated', true)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة خطأ')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['recitation_session_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
            ])
            ->actions([
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
            ->emptyStateHeading('لا توجد أخطاء مسجلة')
            ->emptyStateDescription('لم يتم رصد أي أخطاء في هذه الجلسة.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
