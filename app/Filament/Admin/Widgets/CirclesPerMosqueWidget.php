<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Mosque;
use App\Models\QuranCircle;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CirclesPerMosqueWidget extends BaseWidget
{
    protected static ?string $heading = 'أعداد الحلقات في المساجد';
    
    protected int | string | array $columnSpan = 'full';
    
    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.pages.mosques-and-circles-dashboard');
    }
    
    protected function getTableQuery(): Builder
    {
        // التحقق من وجود الجداول والأعمدة قبل بناء الاستعلام
        if (!Schema::hasTable('mosques') || !Schema::hasTable('quran_circles')) {
            return Mosque::query()->where('id', 0); // استعلام فارغ
        }
        
        if (!Schema::hasColumn('quran_circles', 'mosque_id')) {
            return Mosque::query()->where('id', 0); // استعلام فارغ
        }
        
        return Mosque::query()
            ->select([
                'mosques.id',
                'mosques.name',
                'mosques.region',
                DB::raw('COUNT(quran_circles.id) as circles_count')
            ])
            ->leftJoin('quran_circles', 'mosques.id', '=', 'quran_circles.mosque_id')
            ->groupBy('mosques.id', 'mosques.name', 'mosques.region')
            ->orderByDesc('circles_count');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المسجد')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('region')
                    ->label('المنطقة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('circles_count')
                    ->label('عدد الحلقات')
                    ->sortable()
                    ->alignCenter()
                    ->color('primary')
                    ->badge(),
            ])
            ->defaultSort('circles_count', 'desc')
            ->defaultPaginationPageOption(5);
    }
}