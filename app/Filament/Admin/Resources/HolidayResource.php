<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\HolidayResource\Pages;
use App\Filament\Admin\Resources\HolidayResource\RelationManagers;
use App\Models\Holiday;
use App\Models\AcademicCalendar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    // تعيين أيقونة مناسبة للإجازات
    protected static ?string $navigationIcon = 'heroicon-o-sun';
    
    // تعيين العنوان بالعربية
    protected static ?string $label = 'إجازة';
    protected static ?string $pluralLabel = 'الإجازات';
    
    // وضع المورد في مجموعة التنقل المناسبة
    protected static ?string $navigationGroup = 'التعليمية';
    
    // ترتيب المورد في القائمة
    protected static ?int $navigationSort = 6;

    /**
     * إظهار عدد أيام الإجازة في مربع العدد (Badge) في القائمة
     */
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    
    /**
     * تحديد لون مربع العدد (Badge) في القائمة
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('academic_calendar_id')
                    ->label('التقويم الدراسي')
                    ->relationship('academicCalendar', 'name')
                    ->options(
                        AcademicCalendar::query()->pluck('name', 'id')
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('اسم الإجازة')
                    ->placeholder('مثال: إجازة عيد الفطر')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('start_date')
                    ->label('تاريخ بداية الإجازة')
                    ->required()
                    ->displayFormat('d-m-Y'),
                Forms\Components\DatePicker::make('end_date')
                    ->label('تاريخ نهاية الإجازة')
                    ->required()
                    ->displayFormat('d-m-Y')
                    ->after('start_date'),
                Forms\Components\Toggle::make('is_official')
                    ->label('إجازة رسمية')
                    ->helperText('هل هي إجازة رسمية معتمدة من التعليم')
                    ->default(true),
                Forms\Components\Textarea::make('description')
                    ->label('الوصف')
                    ->placeholder('وصف مختصر للإجازة')
                    ->columnSpanFull(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الإجازة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academicCalendar.name')
                    ->label('التقويم الدراسي')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البداية')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('تاريخ النهاية')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('المدة')
                    ->getStateUsing(function ($record) {
                        $start = \Carbon\Carbon::parse($record->start_date);
                        $end = \Carbon\Carbon::parse($record->end_date);
                        $diffInDays = $end->diffInDays($start) + 1; // +1 to include both start and end days
                        return $diffInDays . ' ' . ($diffInDays > 1 ? 'أيام' : 'يوم');
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_official')
                    ->label('إجازة رسمية')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('academic_calendar_id')
                    ->label('تصفية حسب التقويم الدراسي')
                    ->relationship('academicCalendar', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('future_holidays')
                    ->label('الإجازات القادمة فقط')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('start_date', '>=', now())),
                Tables\Filters\TernaryFilter::make('is_official')
                    ->label('نوع الإجازة')
                    ->placeholder('الكل')
                    ->trueLabel('رسمية فقط')
                    ->falseLabel('غير رسمية فقط'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
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
            'index' => Pages\ListHolidays::route('/'),
            'create' => Pages\CreateHoliday::route('/create'),
            'view' => Pages\ViewHoliday::route('/{record}'),
            'edit' => Pages\EditHoliday::route('/{record}/edit'),
        ];
    }
}
