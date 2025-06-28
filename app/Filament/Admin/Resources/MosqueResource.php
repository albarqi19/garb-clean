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
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\TextInput::make('location_lat')
                            ->label('خط العرض')
                            ->numeric()
                            ->helperText('يمكنك الحصول عليه من خرائط جوجل'),
                        Forms\Components\TextInput::make('location_long')
                            ->label('خط الطول')
                            ->numeric()
                            ->helperText('يمكنك الحصول عليه من خرائط جوجل'),
                    ])->columns(2),
                Forms\Components\Section::make('الحصول على إحداثيات الموقع')
                    ->description('يمكنك استخدام خرائط جوجل للحصول على إحداثيات الموقع. افتح الخريطة، انقر بزر الماوس الأيمن على الموقع المطلوب، ثم انسخ الإحداثيات.')
                    ->schema([
                        Forms\Components\Placeholder::make('google_maps_help')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<a href="https://www.google.com/maps" target="_blank" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-500 active:bg-primary-700 focus:outline-none focus:border-primary-700 focus:ring focus:ring-primary-200 disabled:opacity-25 transition">' .
                                '<svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>' .
                                'فتح خرائط جوجل لتحديد الموقع</a>'
                            )),
                    ]),
                Forms\Components\Section::make('معاينة الموقع')
                    ->schema([
                        Forms\Components\Placeholder::make('map_preview')
                            ->label('رابط الموقع في خرائط جوجل')
                            ->content(function ($get) {
                                $lat = $get('location_lat');
                                $long = $get('location_long');
                                
                                if (!$lat || !$long) {
                                    return 'يرجى إدخال إحداثيات الموقع (خط العرض وخط الطول) لعرض الرابط';
                                }
                                
                                $name = $get('name') ? 'q=' . urlencode($get('name')) . '&' : '';
                                $address = '';
                                
                                if ($get('neighborhood')) {
                                    $address .= $get('neighborhood');
                                }
                                
                                if ($get('street')) {
                                    $address .= ($address ? '، ' : '') . $get('street');
                                }
                                
                                $addressParam = $address ? '&address=' . urlencode($address) : '';
                                
                                $url = "https://www.google.com/maps?{$name}ll={$lat},{$long}{$addressParam}";
                                
                                return new \Illuminate\Support\HtmlString(
                                    '<a href="' . $url . '" target="_blank" class="text-primary-500 hover:underline">' .
                                    '<span class="inline-flex items-center"><svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>' .
                                    'عرض الموقع على خرائط جوجل</span></a><br/>' .
                                    '<div class="mt-2 text-sm text-gray-500" dir="ltr">' . $url . '</div>'
                                );
                            }),
                    ]),
                Forms\Components\TextInput::make('contact_number')
                    ->label('رقم الاتصال')
                    ->tel()
                    ->maxLength(255),
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
                // إضافة عمود رابط خرائط جوجل
                Tables\Columns\TextColumn::make('location')
                    ->label('الموقع')
                    ->formatStateUsing(function ($record) {
                        if ($record->location_lat && $record->location_long) {
                            return 'عرض على الخريطة';
                        }
                        return '-';
                    })
                    ->url(function ($record) {
                        if ($record->location_lat && $record->location_long) {
                            return $record->google_maps_url;
                        }
                        return null;
                    }, true)
                    ->icon('heroicon-o-map-pin')
                    ->color('success')
                    ->hidden(fn ($record) => !$record || !$record->location_lat || !$record->location_long),
                // إضافة عمود لعرض عدد الحلقات في هذا المسجد
                Tables\Columns\TextColumn::make('circles_count')
                    ->label('عدد الحلقات')
                    ->counts('quranCircles')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime('d-m-Y')
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
