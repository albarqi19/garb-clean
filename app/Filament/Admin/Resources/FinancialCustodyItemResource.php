<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\FinancialCustodyItemResource\Pages;
use App\Filament\Admin\Resources\FinancialCustodyItemResource\RelationManagers;
use App\Models\FinancialCustodyItem;
use App\Models\FinancialCustody;
use App\Models\CustodyCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FinancialCustodyItemResource extends Resource
{
    protected static ?string $model = FinancialCustodyItem::class;

    // تعيين أيقونة مناسبة لعناصر العهد المالية
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    // إضافة الترجمات العربية
    protected static ?string $modelLabel = 'عنصر عهدة';
    protected static ?string $pluralModelLabel = 'عناصر العهد المالية';
    
    // تعيين مجموعة التنقل في القائمة
    protected static ?string $navigationGroup = 'المالية';
    
    // ترتيب ظهور المورد في القائمة
    protected static ?int $navigationSort = 80;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // قسم البيانات الأساسية
                Forms\Components\Section::make('البيانات الأساسية')
                    ->schema([
                        Forms\Components\Select::make('financial_custody_id')
                            ->label('العهدة المالية')
                            ->relationship('financialCustody', 'request_number')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => request()->get('custodyId'))
                            ->disabled(fn () => request()->has('custodyId'))
                            ->getOptionLabelFromRecordUsing(fn (FinancialCustody $record) => "{$record->request_number} - {$record->requester->name} ({$record->remaining_amount} ر.س)")
                            ->afterStateHydrated(function ($component, $state, $record) {
                                // عرض المبلغ المتبقي في العهدة المالية المحددة
                                if ($state) {
                                    $custody = FinancialCustody::find($state);
                                    if ($custody) {
                                        $component->helperText("المبلغ المتبقي: {$custody->remaining_amount} ر.س");
                                    }
                                }
                            }),
                        Forms\Components\Select::make('custody_category_id')
                            ->label('التصنيف')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('اسم التصنيف')
                                    ->required(),
                                Forms\Components\TextInput::make('description')
                                    ->label('الوصف')
                                    ->nullable(),
                            ]),
                        Forms\Components\TextInput::make('description')
                            ->label('الوصف')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(1),
                
                // قسم البيانات المالية
                Forms\Components\Section::make('البيانات المالية')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('المبلغ')
                            ->required()
                            ->numeric()
                            ->prefix('ر.س')
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(function () {
                                $custodyId = request()->get('custodyId');
                                if ($custodyId) {
                                    $custody = FinancialCustody::find($custodyId);
                                    return $custody ? $custody->remaining_amount : 0;
                                }
                                return null;
                            })
                            ->helperText('يجب ألا يتجاوز المبلغ المتبقي في العهدة'),
                    ])
                    ->columns(1),
                
                // قسم الملاحظات
                Forms\Components\Section::make('ملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('financialCustody.request_number')
                    ->label('رقم العهدة')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('financialCustody.requester.name')
                    ->label('مقدم الطلب')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('التصنيف')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('financial_custody_id')
                    ->label('العهدة المالية')
                    ->relationship('financialCustody', 'request_number')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('custody_category_id')
                    ->label('التصنيف')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListFinancialCustodyItems::route('/'),
            'create' => Pages\CreateFinancialCustodyItem::route('/create'),
            'edit' => Pages\EditFinancialCustodyItem::route('/{record}/edit'),
        ];
    }
}
