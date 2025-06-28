<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AcademicTermResource\Pages;
use App\Filament\Admin\Resources\AcademicTermResource\RelationManagers;
use App\Models\AcademicTerm;
use App\Models\AcademicCalendar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicTermResource extends Resource
{
    protected static ?string $model = AcademicTerm::class;

    // تعيين أيقونة مناسبة للفصول الدراسية
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    // تعيين العنوان بالعربية
    protected static ?string $label = 'فصل دراسي';
    protected static ?string $pluralLabel = 'الفصول الدراسية';
    
    // وضع المورد في مجموعة التنقل المناسبة
    protected static ?string $navigationGroup = 'التعليمية';
    
    // ترتيب المورد في القائمة
    protected static ?int $navigationSort = 5;
    
    /**
     * إظهار عدد العناصر في مربع العدد (Badge) في القائمة
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
        return 'warning'; // اللون البرتقالي للفصول الدراسية
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
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم التقويم')
                            ->required(),
                        Forms\Components\TextInput::make('academic_year')
                            ->label('العام الدراسي')
                            ->required(),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ بداية التقويم')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('تاريخ نهاية التقويم')
                            ->required(),
                    ]),
                Forms\Components\TextInput::make('name')
                    ->label('اسم الفصل الدراسي')
                    ->placeholder('مثال: الفصل الدراسي الأول')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('start_date')
                    ->label('تاريخ بداية الفصل')
                    ->required()
                    ->displayFormat('d-m-Y'),
                Forms\Components\DatePicker::make('end_date')
                    ->label('تاريخ نهاية الفصل')
                    ->required()
                    ->displayFormat('d-m-Y')
                    ->after('start_date'),
                Forms\Components\Toggle::make('is_current')
                    ->label('الفصل الحالي')
                    ->helperText('تحديد هذا الفصل كالفصل النشط حالياً')
                    ->default(false),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->placeholder('ملاحظات حول الفصل الدراسي')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الفصل')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academicCalendar.name')
                    ->label('التقويم الدراسي')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academicCalendar.academic_year')
                    ->label('العام الدراسي')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البداية')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('تاريخ النهاية')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_current')
                    ->label('الفصل الحالي')
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
                Tables\Filters\TernaryFilter::make('is_current')
                    ->label('الفصل الحالي')
                    ->placeholder('الكل')
                    ->trueLabel('الفصل الحالي فقط')
                    ->falseLabel('الفصول غير الحالية'),
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
            'index' => Pages\ListAcademicTerms::route('/'),
            'create' => Pages\CreateAcademicTerm::route('/create'),
            'view' => Pages\ViewAcademicTerm::route('/{record}'),
            'edit' => Pages\EditAcademicTerm::route('/{record}/edit'),
        ];
    }
}
