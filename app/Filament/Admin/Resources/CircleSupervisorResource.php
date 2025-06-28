<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CircleSupervisorResource\Pages;
use App\Filament\Admin\Resources\CircleSupervisorResource\RelationManagers;
use App\Models\CircleSupervisor;
use App\Models\User;
use App\Models\QuranCircle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CircleSupervisorResource extends Resource
{
    protected static ?string $model = CircleSupervisor::class;

    // تعيين أيقونة مناسبة للمشرفين
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    // إضافة الترجمات العربية
    protected static ?string $modelLabel = 'مشرف حلقة';
    protected static ?string $pluralModelLabel = 'مشرفي الحلقات';
    
    // تعيين مجموعة التنقل في القائمة
    protected static ?string $navigationGroup = 'إدارة المساجد والحلقات';
    
    // ترتيب ظهور المورد في القائمة
    protected static ?int $navigationSort = 14;
    
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
        return 'purple'; // اللون البنفسجي لمشرفي الحلقات
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // قسم بيانات الإشراف
                Forms\Components\Section::make('بيانات الإشراف')
                    ->schema([
                        Forms\Components\Select::make('supervisor_id')
                            ->label('المشرف')
                            ->relationship('supervisor', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('quran_circle_id')
                            ->label('الحلقة القرآنية')
                            ->relationship('quranCircle', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('assignment_date')
                            ->label('تاريخ التكليف')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('تاريخ الإنتهاء')
                            ->after('assignment_date'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط حاليًا')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),
                    ])
                    ->columns(2),

                // قسم ملاحظات الإشراف
                Forms\Components\Section::make('ملاحظات الإشراف')
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
                Tables\Columns\TextColumn::make('supervisor.name')
                    ->label('اسم المشرف')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quranCircle.name')
                    ->label('اسم الحلقة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quranCircle.mosque.name')
                    ->label('المسجد')
                    ->searchable(),
                Tables\Columns\TextColumn::make('assignment_date')
                    ->label('تاريخ التكليف')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('تاريخ الإنتهاء')
                    ->date('Y-m-d')
                    ->sortable()
                    ->placeholder('مستمر'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->sortable()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('visits_count')
                    ->label('عدد الزيارات')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('average_rating')
                    ->label('متوسط التقييم')
                    ->numeric(2)
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . '/5' : '-')
                    ->color(fn ($state) => match(true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'primary',
                        $state >= 2.5 => 'warning',
                        $state !== null => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('جميع الحالات')
                    ->trueLabel('المشرفين النشطين')
                    ->falseLabel('المشرفين غير النشطين'),
                Tables\Filters\SelectFilter::make('supervisor_id')
                    ->label('المشرف')
                    ->relationship('supervisor', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('quran_circle_id')
                    ->label('الحلقة القرآنية')
                    ->relationship('quranCircle', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (CircleSupervisor $record) => $record->is_active ? 'إنهاء الإشراف' : 'تفعيل الإشراف')
                    ->icon(fn (CircleSupervisor $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (CircleSupervisor $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (CircleSupervisor $record): void {
                        $record->update([
                            'is_active' => !$record->is_active,
                            'end_date' => $record->is_active ? now() : null,
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activateBulk')
                        ->label('تفعيل الإشراف')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Builder $query) => $query->update(['is_active' => true, 'end_date' => null])),
                    Tables\Actions\BulkAction::make('deactivateBulk')
                        ->label('إنهاء الإشراف')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (Builder $query) => $query->update(['is_active' => false, 'end_date' => now()])),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // يمكن إضافة علاقات مثل زيارات المشرف
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCircleSupervisors::route('/'),
            'create' => Pages\CreateCircleSupervisor::route('/create'),
            'edit' => Pages\EditCircleSupervisor::route('/{record}/edit'),
        ];
    }

    // تصفية المشرفين النشطين تلقائيًا
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
