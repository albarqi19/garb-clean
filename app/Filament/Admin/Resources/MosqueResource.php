<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MosqueResource\Pages;
use App\Filament\Admin\Resources\MosqueResource\RelationManagers;
use App\Models\Mosque;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MosqueResource extends Resource
{
    protected static ?string $model = Mosque::class;

    // تعيين أيقونة مناسبة للمساجد
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    
    // تعيين العنوان بالعربية
    protected static ?string $label = 'مسجد';
    protected static ?string $pluralLabel = 'المساجد';
    
    // وضع المورد في مجموعة التنقل المناسبة
    protected static ?string $navigationGroup = 'إدارة المساجد والحلقات';
    
    // ترتيب المورد في القائمة
    protected static ?int $navigationSort = 11;
    
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
        return 'warning'; // اللون البرتقالي للمساجد
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم المسجد')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('neighborhood')
                    ->label('الحي')
                    ->maxLength(255),
                Forms\Components\TextInput::make('street')
                    ->label('الشارع')
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_number')
                    ->label('رقم الاتصال')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\TextInput::make('location_lat')
                            ->label('خط العرض')
                            ->numeric()
                            ->helperText('اختياري - للموقع على الخريطة'),
                        Forms\Components\TextInput::make('location_long')
                            ->label('خط الطول')
                            ->numeric()
                            ->helperText('اختياري - للموقع على الخريطة'),
                    ])->columns(2),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المسجد')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('neighborhood')
                    ->label('الحي')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('street')
                    ->label('الشارع')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact_number')
                    ->label('رقم الاتصال')
                    ->searchable(),
                Tables\Columns\TextColumn::make('circles_count')
                    ->label('عدد الحلقات')
                    ->counts('quranCircles')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('neighborhood')
                    ->label('تصفية حسب الحي')
                    ->options(fn(): array => Mosque::query()->pluck('neighborhood', 'neighborhood')->toArray())
                    ->searchable()
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\Action::make('google_maps')
                    ->label('الخريطة')
                    ->icon('heroicon-o-map-pin')
                    ->color('success')
                    ->url(fn ($record) => $record && $record->google_maps_url ? $record->google_maps_url : null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record && $record->location_lat && $record->location_long),
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil'),
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
            RelationManagers\QuranCirclesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMosques::route('/'),
            'create' => Pages\CreateMosque::route('/create'),
            'view' => Pages\ViewMosque::route('/{record}'),
            'edit' => Pages\EditMosque::route('/{record}/edit'),
        ];
    }
}
