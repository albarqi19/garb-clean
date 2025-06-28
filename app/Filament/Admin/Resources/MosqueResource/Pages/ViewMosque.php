<?php

namespace App\Filament\Admin\Resources\MosqueResource\Pages;

use App\Filament\Admin\Resources\MosqueResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\HtmlString;

class ViewMosque extends ViewRecord
{
    protected static string $resource = MosqueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('بيانات المسجد')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('اسم المسجد'),
                        Infolists\Components\TextEntry::make('neighborhood')
                            ->label('الحي'),
                        Infolists\Components\TextEntry::make('street')
                            ->label('الشارع'),
                        Infolists\Components\TextEntry::make('contact_number')
                            ->label('رقم الاتصال'),
                    ])->columns(2),
                    
                Infolists\Components\Section::make('موقع المسجد')
                    ->schema([
                        Infolists\Components\TextEntry::make('location_lat')
                            ->label('خط العرض'),
                        Infolists\Components\TextEntry::make('location_long')
                            ->label('خط الطول'),                        Infolists\Components\TextEntry::make('google_maps_url')
                            ->label('رابط الموقع على الخريطة')
                            ->formatStateUsing(function ($record) {
                                if (!$record || !$record->location_lat || !$record->location_long) {
                                    return 'لا توجد إحداثيات موقع';
                                }
                                
                                $url = $record->google_maps_url;
                                if (!$url) {
                                    return 'لا يمكن إنشاء رابط الموقع';
                                }
                                
                                return new HtmlString(
                                    '<a href="' . $url . '" target="_blank" class="text-primary-500 hover:underline">' .
                                    '<span class="inline-flex items-center"><svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>' .
                                    'عرض الموقع على الخريطة</span></a>'
                                );
                            }),
                    ])->columns(2),
                    
                Infolists\Components\Section::make('إحصائيات')
                    ->schema([
                        Infolists\Components\TextEntry::make('circles_count')
                            ->label('عدد الحلقات القرآنية')
                            ->state(function ($record) {
                                return $record->quranCircles()->count();
                            }),
                    ])->columns(2),
            ]);
    }
}
