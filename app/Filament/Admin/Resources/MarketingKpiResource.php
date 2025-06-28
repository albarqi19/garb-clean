<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MarketingKpiResource\Pages;
use App\Filament\Admin\Resources\MarketingKpiResource\RelationManagers;
use App\Models\MarketingKpi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class MarketingKpiResource extends Resource
{
    protected static ?string $model = MarketingKpi::class;

    // تغيير أيقونة التنقل
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    // إضافة الترجمة للمورد
    protected static ?string $modelLabel = 'مؤشر أداء تسويقي';
    protected static ?string $pluralModelLabel = 'مؤشرات الأداء التسويقية';
    
    // تصنيف المورد ضمن مجموعة التسويق
    protected static ?string $navigationGroup = 'التسويق وإدارة الأداء';
    
    // ترتيب المورد في مجموعة التنقل
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم المؤشر')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('وصف المؤشر')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('unit')
                            ->label('وحدة القياس')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('frequency')
                            ->label('دورية المؤشر')
                            ->options([
                                'شهري' => 'شهري',
                                'ربع سنوي' => 'ربع سنوي',
                                'نصف سنوي' => 'نصف سنوي',
                                'سنوي' => 'سنوي'
                            ])
                            ->required(),
                        Forms\Components\Select::make('calculation_type')
                            ->label('نوع الحساب')
                            ->options([
                                'تراكمي' => 'تراكمي',
                                'متوسط' => 'متوسط',
                                'آخر قيمة' => 'آخر قيمة'
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('weight')
                            ->label('الوزن النسبي')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(0)
                            ->step(0.1),
                        Forms\Components\TextInput::make('target_value')
                            ->label('القيمة المستهدفة')
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->required()
                            ->default(true),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المؤشر')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->label('وحدة القياس')
                    ->searchable(),
                Tables\Columns\TextColumn::make('frequency')
                    ->label('الدورية')
                    ->sortable(),
                Tables\Columns\TextColumn::make('calculation_type')
                    ->label('نوع الحساب')
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight')
                    ->label('الوزن النسبي')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('target_value')
                    ->label('القيمة المستهدفة')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('latest_value.actual_value')
                    ->label('آخر قيمة مسجلة')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('frequency')
                    ->label('الدورية')
                    ->options([
                        'شهري' => 'شهري',
                        'ربع سنوي' => 'ربع سنوي',
                        'نصف سنوي' => 'نصف سنوي',
                        'سنوي' => 'سنوي'
                    ]),
                Tables\Filters\SelectFilter::make('calculation_type')
                    ->label('نوع الحساب')
                    ->options([
                        'تراكمي' => 'تراكمي',
                        'متوسط' => 'متوسط',
                        'آخر قيمة' => 'آخر قيمة'
                    ]),
                Tables\Filters\Filter::make('is_active')
                    ->label('المؤشرات النشطة')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\Action::make('add_value')
                    ->label('إضافة قيمة')
                    ->icon('heroicon-o-plus-circle')
                    ->url(fn (MarketingKpi $record) => route('filament.admin.resources.kpi-values.create', ['kpi_id' => $record->id])),
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                    Tables\Actions\BulkAction::make('toggle_active')
                        ->label('تفعيل/تعطيل المحدد')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->is_active = !$record->is_active;
                                $record->save();
                            }
                        })
                        ->icon('heroicon-o-check-circle'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // سيتم إضافة مدير العلاقة لاحقاً
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketingKpis::route('/'),
            'create' => Pages\CreateMarketingKpi::route('/create'),
            'edit' => Pages\EditMarketingKpi::route('/{record}/edit'),
        ];
    }
}
