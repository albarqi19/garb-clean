<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MarketingActivityResource\Pages;
use App\Filament\Admin\Resources\MarketingActivityResource\RelationManagers;
use App\Models\MarketingActivity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MarketingActivityResource extends Resource
{
    protected static ?string $model = MarketingActivity::class;

    // تغيير أيقونة التنقل
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    
    // إضافة الترجمة للمورد
    protected static ?string $modelLabel = 'نشاط تسويقي';
    protected static ?string $pluralModelLabel = 'الأنشطة التسويقية';
    
    // تصنيف المورد ضمن مجموعة التسويق
    protected static ?string $navigationGroup = 'التسويق وإدارة الأداء';
    
    // ترتيب المورد في مجموعة التنقل
    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان النشاط')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('نوع النشاط')
                            ->options([
                                'منشور إعلامي' => 'منشور إعلامي',
                                'رسالة للداعمين' => 'رسالة للداعمين',
                                'مشروع متجر إلكتروني' => 'مشروع متجر إلكتروني',
                                'مشروع مؤسسة مانحة' => 'مشروع مؤسسة مانحة',
                                'فعالية ميدانية' => 'فعالية ميدانية',
                                'حملة تسويقية' => 'حملة تسويقية',
                                'نوع آخر' => 'نوع آخر'
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('وصف النشاط')
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('activity_date')
                            ->label('تاريخ النشاط')
                            ->required(),
                        Forms\Components\TextInput::make('target_audience')
                            ->label('الجمهور المستهدف')
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->label('حالة النشاط')
                            ->options([
                                'مخطط' => 'مخطط',
                                'قيد التنفيذ' => 'قيد التنفيذ',
                                'مكتمل' => 'مكتمل',
                                'مؤجل' => 'مؤجل',
                                'ملغي' => 'ملغي'
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('platform')
                            ->label('المنصة')
                            ->placeholder('مثال: تويتر، فيسبوك، إنستغرام، الموقع، إلخ')
                            ->maxLength(255),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('reach_count')
                                    ->label('عدد الوصول')
                                    ->numeric()
                                    ->minValue(0),
                                Forms\Components\TextInput::make('interaction_count')
                                    ->label('عدد التفاعلات')
                                    ->numeric()
                                    ->minValue(0),
                            ]),
                        Forms\Components\FileUpload::make('file_attachment')
                            ->label('مرفق ملف')
                            ->directory('marketing-activities')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(5120)
                            ->preserveFilenames(),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('created_by')
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
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان النشاط')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع'),
                Tables\Columns\TextColumn::make('activity_date')
                    ->label('تاريخ النشاط')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'مخطط' => 'gray',
                        'قيد التنفيذ' => 'warning',
                        'مكتمل' => 'success',
                        'مؤجل' => 'info',
                        'ملغي' => 'danger',
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('platform')
                    ->label('المنصة')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('reach_count')
                    ->label('الوصول')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('interaction_count')
                    ->label('التفاعلات')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('بواسطة')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('نوع النشاط')
                    ->options([
                        'منشور إعلامي' => 'منشور إعلامي',
                        'رسالة للداعمين' => 'رسالة للداعمين',
                        'مشروع متجر إلكتروني' => 'مشروع متجر إلكتروني',
                        'مشروع مؤسسة مانحة' => 'مشروع مؤسسة مانحة',
                        'فعالية ميدانية' => 'فعالية ميدانية',
                        'حملة تسويقية' => 'حملة تسويقية',
                        'نوع آخر' => 'نوع آخر'
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('حالة النشاط')
                    ->options([
                        'مخطط' => 'مخطط',
                        'قيد التنفيذ' => 'قيد التنفيذ',
                        'مكتمل' => 'مكتمل',
                        'مؤجل' => 'مؤجل',
                        'ملغي' => 'ملغي'
                    ]),
                Tables\Filters\Filter::make('activity_date')
                    ->label('فترة النشاط')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('activity_date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('activity_date', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('this_month')
                    ->label('أنشطة الشهر الحالي')
                    ->query(function (Builder $query): Builder {
                        return $query
                            ->whereMonth('activity_date', Carbon::now()->month)
                            ->whereYear('activity_date', Carbon::now()->year);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                    Tables\Actions\BulkAction::make('update_status_completed')
                        ->label('تعيين كـ "مكتمل"')
                        ->action(fn ($records) => $records->each->update(['status' => 'مكتمل']))
                        ->deselectRecordsAfterCompletion()
                        ->icon('heroicon-o-check-circle'),
                ]),
            ])
            ->defaultSort('activity_date', 'desc');
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
            'index' => Pages\ListMarketingActivities::route('/'),
            'create' => Pages\CreateMarketingActivity::route('/create'),
            'edit' => Pages\EditMarketingActivity::route('/{record}/edit'),
        ];
    }
    
    // إظهار عدد الأنشطة التسويقية للشهر الحالي
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereMonth('activity_date', Carbon::now()->month)
            ->whereYear('activity_date', Carbon::now()->year)
            ->count();
    }
    
    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'عدد الأنشطة التسويقية في الشهر الحالي';
    }
}
