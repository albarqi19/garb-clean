<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\KpiValueResource\Pages;
use App\Filament\Admin\Resources\KpiValueResource\RelationManagers;
use App\Models\KpiValue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KpiValueResource extends Resource
{
    protected static ?string $model = KpiValue::class;

    // تغيير أيقونة التنقل
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    
    // إضافة الترجمة للمورد
    protected static ?string $modelLabel = 'قيمة مؤشر أداء';
    protected static ?string $pluralModelLabel = 'قيم مؤشرات الأداء';
    
    // تصنيف المورد ضمن مجموعة التسويق
    protected static ?string $navigationGroup = 'التسويق وإدارة الأداء';
    
    // ترتيب المورد في مجموعة التنقل
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('kpi_id')
                            ->label('مؤشر الأداء')
                            ->relationship('kpi', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('period_start_date')
                                    ->label('تاريخ بداية الفترة')
                                    ->required(),
                                Forms\Components\DatePicker::make('period_end_date')
                                    ->label('تاريخ نهاية الفترة')
                                    ->required(),
                            ]),
                        Forms\Components\TextInput::make('period_label')
                            ->label('مسمى الفترة')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: الربع الأول 2023'),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('actual_value')
                                    ->label('القيمة الفعلية')
                                    ->required()
                                    ->numeric()
                                    ->step(0.01),
                                Forms\Components\TextInput::make('target_value')
                                    ->label('القيمة المستهدفة')
                                    ->required()
                                    ->numeric()
                                    ->step(0.01),
                            ]),
                        Forms\Components\TextInput::make('achievement_percentage')
                            ->label('نسبة التحقيق %')
                            ->numeric()
                            ->disabled()
                            ->helperText('يتم حساب القيمة تلقائياً بناء على القيم الفعلية والمستهدفة'),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => auth()->id()),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kpi.name')
                    ->label('مؤشر الأداء')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_label')
                    ->label('الفترة')
                    ->searchable(),
                Tables\Columns\TextColumn::make('period_start_date')
                    ->label('بداية الفترة')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_end_date')
                    ->label('نهاية الفترة')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('actual_value')
                    ->label('القيمة الفعلية')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('target_value')
                    ->label('القيمة المستهدفة')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('achievement_percentage')
                    ->label('نسبة التحقيق %')
                    ->numeric(2)
                    ->sortable()
                    ->color(fn ($record): string => $record->achievement_percentage >= 100 ? 'success' : ($record->achievement_percentage >= 70 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المسجل بواسطة')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kpi_id')
                    ->label('مؤشر الأداء')
                    ->relationship('kpi', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('period')
                    ->label('الفترة الزمنية')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('إلى تاريخ')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('period_end_date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('period_end_date', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('achieved_target')
                    ->label('حققت المستهدف')
                    ->query(fn (Builder $query): Builder => $query->where('achievement_percentage', '>=', 100)),
                Tables\Filters\Filter::make('below_target')
                    ->label('أقل من المستهدف')
                    ->query(fn (Builder $query): Builder => $query->where('achievement_percentage', '<', 100)),
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
            ->defaultSort('period_end_date', 'desc');
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
            'index' => Pages\ListKpiValues::route('/'),
            'create' => Pages\CreateKpiValue::route('/create'),
            'edit' => Pages\EditKpiValue::route('/{record}/edit'),
        ];
    }

    // تعديل المسارات المعروضة في شريط التنقل
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereYear('period_end_date', now()->year)->count();
    }

    // إظهار عدد قيم مؤشرات الأداء المسجلة في العام الحالي
    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'عدد قيم مؤشرات الأداء المسجلة في العام الحالي';
    }
}
