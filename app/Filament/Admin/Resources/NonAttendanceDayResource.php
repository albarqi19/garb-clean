<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\NonAttendanceDayResource\Pages;
use App\Filament\Admin\Resources\NonAttendanceDayResource\RelationManagers;
use App\Models\NonAttendanceDay;
use App\Models\AcademicCalendar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NonAttendanceDayResource extends Resource
{
    protected static ?string $model = NonAttendanceDay::class;

    // تعيين أيقونة مناسبة لأيام التعطيل
    protected static ?string $navigationIcon = 'heroicon-o-x-circle';
    
    // تعيين العنوان بالعربية
    protected static ?string $label = 'طلب إجازة';
    protected static ?string $pluralLabel = 'طلبات الإجازة';
    
    // وضع المورد في مجموعة التنقل المناسبة
    protected static ?string $navigationGroup = 'التعليمية';
    
    // ترتيب المورد في القائمة
    protected static ?int $navigationSort = 7;

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
                Forms\Components\DatePicker::make('date')
                    ->label('تاريخ الإجازة')
                    ->required()
                    ->displayFormat('d-m-Y'),
                Forms\Components\TextInput::make('reason')
                    ->label('سبب الإجازة')
                    ->placeholder('مثال: ظروف شخصية، مرض، إلخ')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_emergency')
                    ->label('إجازة طارئة')
                    ->helperText('تحديد ما إذا كانت الإجازة بسبب حالة طارئة أو مخطط لها')
                    ->default(false),
                Forms\Components\Toggle::make('is_makeup_required')
                    ->label('يتطلب تعويض')
                    ->helperText('تحديد ما إذا كان يجب تعويض أيام الإجازة')
                    ->default(true),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->placeholder('أي ملاحظات إضافية حول طلب الإجازة')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('تاريخ الإجازة')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('سبب الإجازة')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('academicCalendar.name')
                    ->label('التقويم الدراسي')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_emergency')
                    ->label('إجازة طارئة')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_makeup_required')
                    ->label('يتطلب تعويض')
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
                Tables\Filters\Filter::make('future_days')
                    ->label('طلبات الإجازة القادمة فقط')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('date', '>=', now())),
                Tables\Filters\TernaryFilter::make('is_emergency')
                    ->label('نوع الإجازة')
                    ->placeholder('الكل')
                    ->trueLabel('إجازة طارئة فقط')
                    ->falseLabel('مخطط لها فقط'),
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
            ->defaultSort('date', 'desc');
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
            'index' => Pages\ListNonAttendanceDays::route('/'),
            'create' => Pages\CreateNonAttendanceDay::route('/create'),
            'view' => Pages\ViewNonAttendanceDay::route('/{record}'),
            'edit' => Pages\EditNonAttendanceDay::route('/{record}/edit'),
        ];
    }

    /**
     * إظهار عدد أيام الغياب في مربع العدد (Badge) في القائمة
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
}
