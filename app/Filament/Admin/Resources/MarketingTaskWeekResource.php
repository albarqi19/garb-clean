<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MarketingTaskWeekResource\Pages;
use App\Filament\Admin\Resources\MarketingTaskWeekResource\RelationManagers;
use App\Models\MarketingTaskWeek;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MarketingTaskWeekResource extends Resource
{
    protected static ?string $model = MarketingTaskWeek::class;

    // تغيير أيقونة التنقل
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    // إضافة الترجمة للمورد
    protected static ?string $modelLabel = 'أسبوع مهام تسويقية';
    protected static ?string $pluralModelLabel = 'أسابيع المهام التسويقية';
    
    // تصنيف المورد ضمن مجموعة التسويق
    protected static ?string $navigationGroup = 'التسويق وإدارة الأداء';
    
    // ترتيب المورد في مجموعة التنقل
    protected static ?int $navigationSort = 17;
    
    // إخفاء المورد من شريط التنقل لتقليل الازدحام (نظراً لأنه يمكن الوصول إليه من خلال المهام التسويقية)
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الأسبوع')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: الأسبوع الأول من يناير 2025'),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('week_number')
                                    ->label('رقم الأسبوع')
                                    ->required()
                                    ->numeric()
                                    ->default(fn () => Carbon::now()->weekOfYear),
                                Forms\Components\TextInput::make('year')
                                    ->label('السنة')
                                    ->required()
                                    ->numeric()
                                    ->default(fn () => Carbon::now()->year),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('تاريخ البداية')
                                    ->required(),
                                Forms\Components\DatePicker::make('end_date')
                                    ->label('تاريخ النهاية')
                                    ->required(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_current')
                                    ->label('الأسبوع الحالي')
                                    ->helperText('حدد إذا كان هذا هو الأسبوع الحالي الذي يتم العمل عليه')
                                    ->required(),
                                Forms\Components\Toggle::make('is_completed')
                                    ->label('مكتمل')
                                    ->helperText('حدد إذا كانت جميع المهام في هذا الأسبوع مكتملة')
                                    ->required(),
                            ]),
                        Forms\Components\TextInput::make('completion_percentage')
                            ->label('نسبة الإكمال %')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%'),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('created_by')
                            ->default(fn () => auth()->id()),
                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => auth()->id()),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('week_label')
                    ->label('عنوان الأسبوع')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('week_number')
                    ->label('رقم الأسبوع')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->label('السنة')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البداية')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('تاريخ النهاية')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_current')
                    ->label('الحالي')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_completed')
                    ->label('مكتمل')
                    ->boolean()
                    ->sortable(),                \App\Filament\Components\ProgressColumn::make('completion_percentage')
                    ->label('نسبة الإكمال')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_current')
                    ->label('الأسبوع الحالي')
                    ->query(fn (Builder $query): Builder => $query->where('is_current', true)),
                Tables\Filters\Filter::make('is_completed')
                    ->label('الأسابيع المكتملة')
                    ->query(fn (Builder $query): Builder => $query->where('is_completed', true)),
                Tables\Filters\Filter::make('not_completed')
                    ->label('الأسابيع غير المكتملة')
                    ->query(fn (Builder $query): Builder => $query->where('is_completed', false)),
                Tables\Filters\Filter::make('current_month')
                    ->label('أسابيع الشهر الحالي')
                    ->query(function (Builder $query): Builder {
                        $start = Carbon::now()->startOfMonth();
                        $end = Carbon::now()->endOfMonth();
                        
                        return $query->whereBetween('start_date', [$start, $end]);
                    }),
                Tables\Filters\Filter::make('current_year')
                    ->label('أسابيع السنة الحالية')
                    ->query(function (Builder $query): Builder {
                        return $query->whereYear('start_date', Carbon::now()->year);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\Action::make('set_current')
                    ->label('تعيين كأسبوع حالي')
                    ->icon('heroicon-o-flag')
                    ->action(function (MarketingTaskWeek $record): void {
                        // إعادة تعيين الأسبوع الحالي
                        MarketingTaskWeek::where('is_current', true)
                            ->update(['is_current' => false]);
                            
                        // تعيين هذا الأسبوع كحالي
                        $record->is_current = true;
                        $record->save();
                    })
                    ->requiresConfirmation()
                    ->hidden(fn (MarketingTaskWeek $record): bool => $record->is_current),
                Tables\Actions\Action::make('view_tasks')
                    ->label('عرض المهام')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->url(fn (MarketingTaskWeek $record): string => route('filament.admin.resources.marketing-tasks.index', ['tableFilters[marketingTaskWeek][value]' => $record->id])),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                    Tables\Actions\BulkAction::make('mark_completed')
                        ->label('تعيين كمكتملة')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->is_completed = true;
                                $record->completion_percentage = 100;
                                $record->save();
                            }
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->icon('heroicon-o-check-circle'),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketingTaskWeeks::route('/'),
            'create' => Pages\CreateMarketingTaskWeek::route('/create'),
            'edit' => Pages\EditMarketingTaskWeek::route('/{record}/edit'),
        ];
    }
}
