<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BranchFollowerResource\Pages;
use App\Filament\Admin\Resources\BranchFollowerResource\RelationManagers;
use App\Models\BranchFollower;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BranchFollowerResource extends Resource
{
    protected static ?string $model = BranchFollower::class;

    // تغيير أيقونة التنقل
    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    // إضافة الترجمة للمورد
    protected static ?string $modelLabel = 'متابع للفرع';
    protected static ?string $pluralModelLabel = 'متابعي الفروع';
    
    // تصنيف المورد ضمن مجموعة التسويق
    protected static ?string $navigationGroup = 'التسويق وإدارة الأداء';
    
    // ترتيب المورد في مجموعة التنقل
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\Select::make('source')
                            ->label('مصدر المتابعة')
                            ->options([
                                'حلقة القرآن' => 'حلقة القرآن',
                                'المسجد' => 'المسجد',
                                'الموقع الإلكتروني' => 'الموقع الإلكتروني',
                                'وسائل التواصل الاجتماعي' => 'وسائل التواصل الاجتماعي',
                                'فعالية تسويقية' => 'فعالية تسويقية',
                                'مصدر آخر' => 'مصدر آخر'
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('is_donor')
                            ->label('متبرع')
                            ->helperText('حدد إذا كان المتابع قد شارك سابقاً بتبرع مالي')
                            ->required(),
                        Forms\Components\DatePicker::make('registration_date')
                            ->label('تاريخ التسجيل')
                            ->default(now())
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('registered_by')
                            ->default(fn () => auth()->id()),
                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => auth()->id()),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('source')
                    ->label('مصدر المتابعة')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_donor')
                    ->label('متبرع')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('registration_date')
                    ->label('تاريخ التسجيل')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('registeredByUser.name')
                    ->label('سجل بواسطة')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->label('مصدر المتابعة')
                    ->options([
                        'حلقة القرآن' => 'حلقة القرآن',
                        'المسجد' => 'المسجد',
                        'الموقع الإلكتروني' => 'الموقع الإلكتروني',
                        'وسائل التواصل الاجتماعي' => 'وسائل التواصل الاجتماعي',
                        'فعالية تسويقية' => 'فعالية تسويقية',
                        'مصدر آخر' => 'مصدر آخر'
                    ]),
                Tables\Filters\Filter::make('is_donor')
                    ->label('المتبرعون')
                    ->query(fn (Builder $query): Builder => $query->where('is_donor', true)),
                Tables\Filters\Filter::make('registration_date')
                    ->label('تاريخ التسجيل')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('إلى تاريخ')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('registration_date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('registration_date', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('registered_this_month')
                    ->label('المسجلون هذا الشهر')
                    ->query(function (Builder $query): Builder {
                        return $query
                            ->whereMonth('registration_date', Carbon::now()->month)
                            ->whereYear('registration_date', Carbon::now()->year);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                    Tables\Actions\BulkAction::make('mark_as_donor')
                        ->label('تعيين كمتبرعين')
                        ->action(fn (Collection $records) => $records->each->update(['is_donor' => true]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->icon('heroicon-o-currency-dollar'),
                ]),
            ])
            ->defaultSort('registration_date', 'desc');
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
            'index' => Pages\ListBranchFollowers::route('/'),
            'create' => Pages\CreateBranchFollower::route('/create'),
            'edit' => Pages\EditBranchFollower::route('/{record}/edit'),
        ];
    }

    // إظهار عدد المتابعين المسجلين في الشهر الحالي
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereMonth('registration_date', Carbon::now()->month)
            ->whereYear('registration_date', Carbon::now()->year)
            ->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'عدد المتابعين المسجلين في الشهر الحالي';
    }
}
