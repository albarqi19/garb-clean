<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MarketingTaskCompletionResource\Pages;
use App\Filament\Admin\Resources\MarketingTaskCompletionResource\RelationManagers;
use App\Models\MarketingTaskCompletion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use App\Models\User;

class MarketingTaskCompletionResource extends Resource
{
    protected static ?string $model = MarketingTaskCompletion::class;

    // تغيير أيقونة التنقل
    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    
    // إضافة الترجمة للمورد
    protected static ?string $modelLabel = 'إتمام مهمة تسويقية';
    protected static ?string $pluralModelLabel = 'إتمام المهام التسويقية';
    
    // تصنيف المورد ضمن مجموعة التسويق
    protected static ?string $navigationGroup = 'التسويق وإدارة الأداء';
    
    // ترتيب المورد في مجموعة التنقل
    protected static ?int $navigationSort = 18;
    
    // إخفاء المورد من شريط التنقل لتقليل الازدحام (نظراً لأنه يمكن الوصول إليه من خلال المهام التسويقية)
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('marketing_task_id')
                            ->label('المهمة التسويقية')
                            ->relationship('marketingTask', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('completed_by')
                            ->label('تم الإنجاز بواسطة')
                            ->options(User::all()->pluck('name', 'id'))
                            ->default(function() {
                                return auth()->check() ? auth()->id() : null;
                            })
                            ->searchable(),
                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => auth()->id()),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('completion_date')
                                    ->label('تاريخ ووقت الإتمام')
                                    ->default(now())
                                    ->required(),
                                Forms\Components\Toggle::make('is_completed')
                                    ->label('تمت بنجاح')
                                    ->required()
                                    ->default(true),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('week_number')
                                    ->label('رقم الأسبوع')
                                    ->required()
                                    ->numeric()
                                    ->default(fn () => Carbon::now()->weekOfYear)
                                    ->disabled(),
                                Forms\Components\TextInput::make('year')
                                    ->label('السنة')
                                    ->required()
                                    ->numeric()
                                    ->default(fn () => Carbon::now()->year)
                                    ->disabled(),
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات وتعليقات حول الإنجاز')
                            ->placeholder('أضف أي ملاحظات أو تعليقات حول إتمام المهمة...')
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('marketingTask.title')
                    ->label('المهمة التسويقية')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('تم الإنجاز بواسطة')
                    ->sortable(),
                Tables\Columns\TextColumn::make('completion_date')
                    ->label('تاريخ الإتمام')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_completed')
                    ->label('تمت بنجاح')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('week_number')
                    ->label('الأسبوع')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->label('السنة')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('marketing_task_id')
                    ->label('المهمة التسويقية')
                    ->relationship('marketingTask', 'title'),
                Tables\Filters\Filter::make('completion_date')
                    ->label('تاريخ الإتمام')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('completion_date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('completion_date', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('is_completed')
                    ->label('تمت بنجاح')
                    ->query(fn (Builder $query): Builder => $query->where('is_completed', true)),
                Tables\Filters\Filter::make('today')
                    ->label('إنجازات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('completion_date', Carbon::today())),
                Tables\Filters\Filter::make('current_week')
                    ->label('إنجازات الأسبوع الحالي')
                    ->query(function (Builder $query): Builder {
                        return $query->where([
                            ['week_number', Carbon::now()->weekOfYear],
                            ['year', Carbon::now()->year],
                        ]);
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
            ->defaultSort('completion_date', 'desc');
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
            'index' => Pages\ListMarketingTaskCompletions::route('/'),
            'create' => Pages\CreateMarketingTaskCompletion::route('/create'),
            'edit' => Pages\EditMarketingTaskCompletion::route('/{record}/edit'),
        ];
    }
}
