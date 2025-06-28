<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CircleIncentiveResource\Pages;
use App\Models\CircleIncentive;
use App\Models\QuranCircle;
use App\Models\AcademicTerm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CircleIncentiveResource extends Resource
{
    protected static ?string $model = CircleIncentive::class;

    // تعيين أيقونة مناسبة لحوافز الحلقات
    protected static ?string $navigationIcon = 'heroicon-o-gift';
    
    // إضافة الترجمات العربية
    protected static ?string $modelLabel = 'حافز حلقة';
    protected static ?string $pluralModelLabel = 'حوافز الحلقات';
    
    // تعيين مجموعة التنقل في القائمة
    protected static ?string $navigationGroup = 'المالية';
    
    // ترتيب ظهور المورد في القائمة
    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // قسم البيانات الأساسية
                Forms\Components\Section::make('البيانات الأساسية')
                    ->schema([
                        Forms\Components\Select::make('quran_circle_id')
                            ->label('الحلقة القرآنية')
                            ->relationship('quranCircle', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('اسم الحلقة')
                                    ->required(),
                                Forms\Components\Select::make('mosque_id')
                                    ->label('المسجد')
                                    ->relationship('mosque', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                        Forms\Components\Select::make('academic_term_id')
                            ->label('الفصل الدراسي')
                            ->relationship('academicTerm', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('sponsor_name')
                            ->label('اسم الراعي')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('month')
                            ->label('الشهر')
                            ->placeholder('مثال: ديسمبر 2023')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                
                // قسم بيانات المبالغ
                Forms\Components\Section::make('بيانات المبالغ')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('المبلغ الإجمالي')
                            ->required()
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01)
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set, $state) => $set('remaining_amount', $state)),
                        Forms\Components\TextInput::make('remaining_amount')
                            ->label('المبلغ المتبقي')
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01)
                            ->default(0),
                    ])
                    ->columns(2),
                
                // قسم بيانات الحالة
                Forms\Components\Section::make('بيانات الحالة')
                    ->schema([
                        Forms\Components\DatePicker::make('allocation_date')
                            ->label('تاريخ التخصيص')
                            ->required()
                            ->default(now()),
                        Forms\Components\Toggle::make('is_blocked')
                            ->label('منع الصرف')
                            ->helperText('عند التفعيل، سيتم منع صرف الحافز بسبب عدم وجود فائض كافي')
                            ->default(false)
                            ->onColor('danger')
                            ->offColor('success'),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quranCircle.name')
                    ->label('اسم الحلقة')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('quranCircle.mosque.name')
                    ->label('المسجد')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('sponsor_name')
                    ->label('اسم الراعي')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ الإجمالي')
                    ->money('SAR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('المبلغ المتبقي')
                    ->money('SAR')
                    ->sortable()
                    ->color(fn ($state, $record) => $state > 0 ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('month')
                    ->label('الشهر')
                    ->searchable(),
                Tables\Columns\TextColumn::make('academicTerm.name')
                    ->label('الفصل الدراسي')
                    ->sortable(),
                Tables\Columns\TextColumn::make('distribution_percentage')
                    ->label('نسبة الصرف')
                    ->numeric(2)
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->sortable()
                    ->color(fn ($state) => match (true) {
                        $state >= 90 => 'success',
                        $state >= 50 => 'warning',
                        $state > 0 => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_blocked')
                    ->label('منع الصرف')
                    ->boolean()
                    ->sortable()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
                Tables\Columns\TextColumn::make('allocation_date')
                    ->label('تاريخ التخصيص')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('quran_circle_id')
                    ->label('الحلقة القرآنية')
                    ->relationship('quranCircle', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('academic_term_id')
                    ->label('الفصل الدراسي')
                    ->relationship('academicTerm', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_blocked')
                    ->label('حالة الصرف')
                    ->trueLabel('ممنوع الصرف')
                    ->falseLabel('متاح للصرف'),
                Tables\Filters\Filter::make('has_remaining')
                    ->label('متبقي للصرف')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('remaining_amount', '>', 0)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('updateBlockStatus')
                    ->label('تحديث الحالة')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->action(function (CircleIncentive $record) {
                        $record->updateBlockStatus();
                    }),
                Tables\Actions\Action::make('addTeacherIncentive')
                    ->label('إضافة حافز لمعلم')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn (CircleIncentive $record) => !$record->is_blocked && $record->remaining_amount > 0)
                    ->url(fn (CircleIncentive $record): string => route('filament.admin.resources.teacher-incentives.create', ['circleIncentiveId' => $record->id])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('updateAllStatuses')
                        ->label('تحديث جميع الحالات')
                        ->icon('heroicon-o-arrow-path')
                        ->action(fn () => CircleIncentive::updateAllIncentiveStatuses())
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
            'index' => Pages\ListCircleIncentives::route('/'),
            'create' => Pages\CreateCircleIncentive::route('/create'),
            'edit' => Pages\EditCircleIncentive::route('/{record}/edit'),
        ];
    }
    
    // دالة للحصول على الاستعلام الأولي
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
