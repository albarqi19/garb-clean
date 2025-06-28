<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ExpenseTypeResource\Pages;
use App\Filament\Admin\Resources\ExpenseTypeResource\RelationManagers;
use App\Models\ExpenseType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseTypeResource extends Resource
{
    protected static ?string $model = ExpenseType::class;

    // تعيين الأيقونة إلى أيقونة المصروفات
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    // الترجمة العربية للمورد
    protected static ?string $modelLabel = 'نوع مصروف';
    protected static ?string $pluralModelLabel = 'أنواع المصروفات';
    
    // تعيين مجموعة التنقل إلى "المالية"
    protected static ?string $navigationGroup = 'المالية';
    
    // ترتيب الظهور في القائمة
    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم النوع')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\Textarea::make('description')
                    ->label('الوصف')
                    ->columnSpanFull(),
                    
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم النوع')
                    ->searchable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                Tables\Columns\TextColumn::make('expenses_count')
                    ->label('عدد المصروفات')
                    ->counts('expenses')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التعديل')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط')
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                    
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (ExpenseType $record): string => $record->is_active ? 'تعطيل' : 'تفعيل')
                    ->icon(fn (ExpenseType $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (ExpenseType $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (ExpenseType $record) => $record->update(['is_active' => !$record->is_active])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                        
                    Tables\Actions\BulkAction::make('activate')
                        ->label('تفعيل المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (array $records) => ExpenseType::whereIn('id', $records)->update(['is_active' => true])),
                        
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('تعطيل المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (array $records) => ExpenseType::whereIn('id', $records)->update(['is_active' => false])),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ExpensesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenseTypes::route('/'),
            'create' => Pages\CreateExpenseType::route('/create'),
            'edit' => Pages\EditExpenseType::route('/{record}/edit'),
        ];
    }
}
