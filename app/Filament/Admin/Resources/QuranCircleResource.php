<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\QuranCircleResource\Pages;
use App\Filament\Admin\Resources\QuranCircleResource\RelationManagers;
use App\Models\QuranCircle;
use App\Models\Mosque;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuranCircleResource extends Resource
{
    protected static ?string $model = QuranCircle::class;

    // تعيين أيقونة مناسبة للحلقات القرآنية
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    
    // تعيين العنوان بالعربية
    protected static ?string $label = 'مدرسة قرآنية';
    protected static ?string $pluralLabel = 'المدارس القرآنية';
      // وضع المورد في مجموعة التنقل المناسبة
    protected static ?string $navigationGroup = 'إدارة المساجد والحلقات';
    
    // ترتيب المورد في القائمة
    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم الحلقة')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('mosque_id')
                    ->label('المسجد')
                    ->relationship('mosque', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('circle_type')
                    ->label('نوع الحلقة')
                    ->required()
                    ->options([
                        'حلقة فردية' => 'حلقة فردية',
                        'حلقة جماعية' => 'حلقة جماعية',
                    ]),
                Forms\Components\Select::make('circle_status')
                    ->label('حالة الحلقة')
                    ->required()
                    ->options([
                        'نشطة' => 'نشطة',
                        'معلقة' => 'معلقة',
                        'مغلقة' => 'مغلقة',
                    ]),
                Forms\Components\Select::make('time_period')
                    ->label('الفترة الزمنية')
                    ->required()
                    ->options([
                        'عصر' => 'عصر',
                        'مغرب' => 'مغرب',
                        'عصر ومغرب' => 'عصر ومغرب',
                        'كل الأوقات' => 'كل الأوقات',
                        'صباحية' => 'صباحية',
                        'مسائية' => 'مسائية',
                        'ليلية' => 'ليلية',
                        'الفجر' => 'الفجر',
                    ]),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الحلقة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mosque.name')
                    ->label('المسجد')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('circle_type')
                    ->label('نوع الحلقة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'حلقة فردية' => 'success',
                        'حلقة جماعية' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('circle_status')
                    ->label('حالة الحلقة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'نشطة' => 'success',
                        'معلقة' => 'warning',
                        'مغلقة' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_period')
                    ->label('الفترة')
                    ->sortable(),
                Tables\Columns\IconColumn::make('has_ratel')
                    ->label('رتل')
                    ->boolean(),
                Tables\Columns\IconColumn::make('has_qias')
                    ->label('قياس')
                    ->boolean(),
                Tables\Columns\TextColumn::make('supervisor.name')
                    ->label('المشرف')
                    ->sortable(),
                Tables\Columns\TextColumn::make('monitor.name')
                    ->label('المراقب')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('mosque_id')
                    ->label('تصفية حسب المسجد')
                    ->relationship('mosque', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('circle_type')
                    ->label('نوع الحلقة')
                    ->options([
                        'حلقة فردية' => 'حلقة فردية',
                        'حلقة جماعية' => 'حلقة جماعية',
                    ]),
                Tables\Filters\SelectFilter::make('circle_status')
                    ->label('حالة الحلقة')
                    ->options([
                        'نشطة' => 'نشطة',
                        'معلقة' => 'معلقة',
                        'مغلقة' => 'مغلقة',
                    ]),                Tables\Filters\SelectFilter::make('time_period')
                    ->label('الفترة الزمنية')
                    ->options([
                        'عصر' => 'عصر',
                        'مغرب' => 'مغرب',
                        'عصر ومغرب' => 'عصر ومغرب',
                        'كل الأوقات' => 'كل الأوقات',
                        'صباحية' => 'صباحية',
                        'مسائية' => 'مسائية',
                        'ليلية' => 'ليلية',
                        'الفجر' => 'الفجر',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\Action::make('view_circle_groups')
                    ->label('الحلقات الفرعية')
                    ->icon('heroicon-o-user-group')
                    ->color('success')
                    ->url(fn (QuranCircle $record): string => 
                        $record->circle_type === 'حلقة جماعية' 
                            ? static::getUrl('view', ['record' => $record->id]) . '#relation-manager-quranCircle-circle-groups-relation-manager' 
                            : '#'
                    )
                    ->visible(fn (QuranCircle $record): bool => $record->circle_type === 'حلقة جماعية'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ]);
    }    public static function getRelations(): array
    {
        return [
            RelationManagers\TeacherRelationManager::class,
            RelationManagers\TeacherAssignmentsRelationManager::class,
            RelationManagers\CircleGroupsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranCircles::route('/'),
            'create' => Pages\CreateQuranCircle::route('/create'),
            'view' => Pages\ViewQuranCircle::route('/{record}'),
            'edit' => Pages\EditQuranCircle::route('/{record}/edit'),
        ];
    }
    
    /**
     * إظهار عدد الحلقات النشطة في مربع العدد (Badge) في القائمة
     */
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('circle_status', 'نشطة')->count();
    }
    
    /**
     * تحديد لون مربع العدد (Badge) في القائمة
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
