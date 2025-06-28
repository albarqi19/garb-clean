<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AcademicCalendarResource\Pages;
use App\Filament\Admin\Resources\AcademicCalendarResource\RelationManagers;
use App\Models\AcademicCalendar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicCalendarResource extends Resource
{
    protected static ?string $model = AcademicCalendar::class;

    // تعيين أيقونة مناسبة للتقويم الدراسي
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    
    // تعيين العنوان بالعربية
    protected static ?string $label = 'تقويم دراسي';
    protected static ?string $pluralLabel = 'التقاويم الدراسية';
    
    // وضع المورد في مجموعة التنقل المناسبة
    protected static ?string $navigationGroup = 'التعليمية';
    
    // ترتيب المورد في القائمة
    protected static ?int $navigationSort = 4;
    
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
        return 'success'; // اللون الأخضر للتقاويم الدراسية
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات التقويم الدراسي')
                    ->schema([
                        Forms\Components\TextInput::make('academic_year')
                            ->label('العام الدراسي')
                            ->required()
                            ->placeholder('مثال: 1446-1447')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->label('اسم التقويم')
                            ->required()
                            ->placeholder('مثال: التقويم الدراسي للعام 1446-1447')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ البداية')
                            ->required()
                            ->displayFormat('d-m-Y'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('تاريخ النهاية')
                            ->required()
                            ->displayFormat('d-m-Y')
                            ->after('start_date'),
                        Forms\Components\Toggle::make('is_current')
                            ->label('التقويم الحالي')
                            ->helperText('تحديد هذا التقويم كالتقويم النشط حالياً')
                            ->default(false),
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->placeholder('وصف مختصر للتقويم الدراسي')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم التقويم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academic_year')
                    ->label('العام الدراسي')
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
                Tables\Columns\TextColumn::make('academicTerms_count')
                    ->label('عدد الفصول الدراسية')
                    ->counts('academicTerms')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_current')
                    ->label('التقويم الحالي')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_current')
                    ->label('التقويم الحالي')
                    ->placeholder('الكل')
                    ->trueLabel('التقويم الحالي فقط')
                    ->falseLabel('التقاويم غير الحالية'),
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
            RelationManagers\AcademicTermsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicCalendars::route('/'),
            'create' => Pages\CreateAcademicCalendar::route('/create'),
            'view' => Pages\ViewAcademicCalendar::route('/{record}'),
            'edit' => Pages\EditAcademicCalendar::route('/{record}/edit'),
        ];
    }
}
