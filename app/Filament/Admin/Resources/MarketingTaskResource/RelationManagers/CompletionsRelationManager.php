<?php

namespace App\Filament\Admin\Resources\MarketingTaskResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\User;
use Carbon\Carbon;

class CompletionsRelationManager extends RelationManager
{
    protected static string $relationship = 'completions';

    // تعريب العناوين
    protected static ?string $title = 'سجلات الإنجاز';
    protected static ?string $modelLabel = 'سجل إنجاز';
    protected static ?string $pluralModelLabel = 'سجلات الإنجاز';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الإنجاز')
                    ->schema([
                        Forms\Components\Toggle::make('is_completed')
                            ->label('تم الإنجاز')
                            ->default(true)
                            ->required(),
                        
                        Forms\Components\DateTimePicker::make('completion_date')
                            ->label('تاريخ الإنجاز')
                            ->default(now())
                            ->required(),
                            
                        Forms\Components\Select::make('completed_by')
                            ->label('المنجز بواسطة')
                            ->options(User::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات الإنجاز')
                            ->placeholder('أضف ملاحظاتك حول إنجاز المهمة')
                            ->maxLength(255)
                            ->columnSpanFull(),
                            
                        Forms\Components\Hidden::make('week_number')
                            ->default(fn() => Carbon::now()->weekOfYear),
                            
                        Forms\Components\Hidden::make('year')
                            ->default(fn() => Carbon::now()->year),
                    ])
                    ->columns(2)
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\IconColumn::make('is_completed')
                    ->label('تم الإنجاز')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                    
                Tables\Columns\TextColumn::make('completion_date')
                    ->label('تاريخ الإنجاز')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('week_number')
                    ->label('رقم الأسبوع')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('year')
                    ->label('السنة')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('completedBy.name')
                    ->label('المنجز بواسطة')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('notes')
                    ->label('الملاحظات')
                    ->limit(30)
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_completed')
                    ->label('حالة الإنجاز')
                    ->trueLabel('المنجزة فقط')
                    ->falseLabel('غير المنجزة فقط')
                    ->placeholder('جميع السجلات'),
                    
                Tables\Filters\SelectFilter::make('year')
                    ->label('السنة')
                    ->options(function() {
                        $years = [];
                        $currentYear = (int) date('Y');
                        for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
                            $years[$i] = $i;
                        }
                        return $years;
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة سجل إنجاز جديد'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('completion_date', 'desc');
    }
}