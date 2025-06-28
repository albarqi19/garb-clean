<?php

namespace App\Filament\Admin\Resources\StrategicIndicatorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MonitoringsRelationManager extends RelationManager
{
    protected static string $relationship = 'monitorings';
    
    // تعريب مدير العلاقة
    protected static ?string $title = 'عمليات الرصد';
    protected static ?string $modelLabel = 'عملية رصد';
    protected static ?string $pluralModelLabel = 'عمليات الرصد';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Select::make('year')
                            ->label('السنة')
                            ->options(function () {
                                $years = [];
                                $startYear = 2023;
                                $endYear = date('Y') + 2;
                                for ($i = $startYear; $i <= $endYear; $i++) {
                                    $years[$i] = $i;
                                }
                                return $years;
                            })
                            ->default(date('Y'))
                            ->required(),
                            
                        Forms\Components\Select::make('period')
                            ->label('الفترة')
                            ->options([
                                'first_quarter' => 'الربع الأول',
                                'second_quarter' => 'الربع الثاني',
                                'third_quarter' => 'الربع الثالث',
                                'fourth_quarter' => 'الربع الرابع',
                                'mid_year' => 'نصف العام',
                                'full_year' => 'نهاية العام',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
                    
                Forms\Components\TextInput::make('actual_value')
                    ->label('القيمة الفعلية')
                    ->required()
                    ->numeric(),
                    
                Forms\Components\TextInput::make('target_value')
                    ->label('القيمة المستهدفة للفترة')
                    ->required()
                    ->numeric(),
                    
                Forms\Components\TextInput::make('achievement_percentage')
                    ->label('نسبة الإنجاز')
                    ->numeric()
                    ->suffix('%')
                    ->required()
                    ->minValue(0)
                    ->maxValue(100)
                    ->helperText('نسبة الإنجاز من 0 إلى 100%'),
                    
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3)
                    ->columnSpanFull(),
                    
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => Auth::id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('year')
            ->columns([
                Tables\Columns\TextColumn::make('year')
                    ->label('السنة')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('period')
                    ->label('الفترة')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'first_quarter' => 'الربع الأول',
                        'second_quarter' => 'الربع الثاني',
                        'third_quarter' => 'الربع الثالث',
                        'fourth_quarter' => 'الربع الرابع',
                        'mid_year' => 'نصف العام',
                        'full_year' => 'نهاية العام',
                        default => $state,
                    })
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
                    ->label('نسبة الإنجاز')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->sortable()
                    ->color(fn ($state) => match(true) {
                        $state >= 100 => 'success',
                        $state >= 70 => 'warning',
                        default => 'danger',
                    }),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('تم بواسطة')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الرصد')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('حسب السنة')
                    ->options(function () {
                        $years = [];
                        $startYear = 2023;
                        $endYear = date('Y') + 1;
                        for ($i = $startYear; $i <= $endYear; $i++) {
                            $years[$i] = $i;
                        }
                        return $years;
                    }),
                    
                Tables\Filters\SelectFilter::make('period')
                    ->label('حسب الفترة')
                    ->options([
                        'first_quarter' => 'الربع الأول',
                        'second_quarter' => 'الربع الثاني',
                        'third_quarter' => 'الربع الثالث',
                        'fourth_quarter' => 'الربع الرابع',
                        'mid_year' => 'نصف العام',
                        'full_year' => 'نهاية العام',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة عملية رصد')
                    ->mutateFormDataUsing(function (array $data): array {
                        // إذا لم يتم إدخال نسبة الإنجاز، نحسبها تلقائياً
                        if (!isset($data['achievement_percentage']) || $data['achievement_percentage'] == 0) {
                            if ($data['target_value'] > 0) {
                                $percentage = ($data['actual_value'] / $data['target_value']) * 100;
                                $data['achievement_percentage'] = min(100, $percentage);
                            } else {
                                $data['achievement_percentage'] = 0;
                            }
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->mutateFormDataUsing(function (array $data): array {
                            if ($data['target_value'] > 0) {
                                $percentage = ($data['actual_value'] / $data['target_value']) * 100;
                                $data['achievement_percentage'] = min(100, $percentage);
                            }
                            return $data;
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn ($record) => $record->user_id === Auth::id() || Auth::user()->hasRole('admin')),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->hasRole('admin')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}