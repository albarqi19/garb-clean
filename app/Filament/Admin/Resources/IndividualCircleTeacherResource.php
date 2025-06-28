<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\IndividualCircleTeacherResource\Pages;
use App\Filament\Admin\Resources\IndividualCircleTeacherResource\RelationManagers;
use App\Models\IndividualCircleTeacher;
use App\Models\QuranCircle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IndividualCircleTeacherResource extends Resource
{
    protected static ?string $model = IndividualCircleTeacher::class;

    // تعيين أيقونة مناسبة لمعلمي الحلقات الفردية
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    // تعيين العنوان بالعربية
    protected static ?string $label = 'حلقة فردية';
    protected static ?string $pluralLabel = 'الحلقات الفردية';
    
    // وضع المورد في مجموعة التنقل المناسبة
    protected static ?string $navigationGroup = 'إدارة المساجد والحلقات';
    
    // ترتيب المورد في القائمة
    protected static ?int $navigationSort = 15;
    
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
        return 'info'; // اللون الأزرق الفاتح للحلقات الفردية
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات المعلم الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم المعلم')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الجوال')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\Select::make('nationality')
                            ->label('الجنسية')
                            ->options([
                                'سعودي' => 'سعودي',
                                'مصري' => 'مصري',
                                'سوداني' => 'سوداني',
                                'يمني' => 'يمني',
                                'سوري' => 'سوري',
                                'أردني' => 'أردني',
                                'فلسطيني' => 'فلسطيني',
                                'باكستاني' => 'باكستاني',
                                'هندي' => 'هندي',
                                'بنجلاديشي' => 'بنجلاديشي',
                                'أخرى' => 'أخرى',
                            ]),
                        Forms\Components\TextInput::make('national_id')
                            ->label('رقم الهوية')
                            ->maxLength(20),
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('معلومات الحلقة')
                    ->schema([
                        Forms\Components\Select::make('circle_id')
                            ->label('الحلقة القرآنية')
                            ->relationship('circle', 'name', function (Builder $query) {
                                return $query->where('circle_type', 'حلقة فردية');
                            })
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('اسم الحلقة')
                                    ->required(),
                                Forms\Components\Select::make('mosque_id')
                                    ->label('المسجد')
                                    ->relationship('mosque', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\Hidden::make('circle_type')
                                    ->default('حلقة فردية'),
                                Forms\Components\Select::make('circle_status')
                                    ->label('حالة الحلقة')
                                    ->options([
                                        'نشطة' => 'نشطة',
                                        'معلقة' => 'معلقة',
                                        'مغلقة' => 'مغلقة',
                                    ])
                                    ->default('نشطة')
                                    ->required(),
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('is_verified')
                            ->label('تم التحقق')
                            ->helperText('هل تم التحقق من بيانات المعلم وصلاحيته للتدريس')
                            ->default(false),
                        Forms\Components\DatePicker::make('joining_date')
                            ->label('تاريخ الانضمام')
                            ->default(now()),
                        Forms\Components\TextInput::make('hourly_rate')
                            ->label('أجر الساعة')
                            ->numeric()
                            ->prefix('ر.س'),
                    ])->columns(2),

                Forms\Components\Section::make('ملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المعلم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('circle.name')
                    ->label('الحلقة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('circle.mosque.name')
                    ->label('المسجد')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الجوال')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nationality')
                    ->label('الجنسية')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('تم التحقق')
                    ->boolean(),
                Tables\Columns\TextColumn::make('hourly_rate')
                    ->label('أجر الساعة')
                    ->money('sar')
                    ->sortable(),
                Tables\Columns\TextColumn::make('joining_date')
                    ->label('تاريخ الانضمام')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('circle_id')
                    ->label('تصفية حسب الحلقة')
                    ->relationship('circle', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('nationality')
                    ->label('تصفية حسب الجنسية')
                    ->options([
                        'سعودي' => 'سعودي',
                        'مصري' => 'مصري',
                        'سوداني' => 'سوداني',
                        'يمني' => 'يمني',
                        'سوري' => 'سوري',
                        'أردني' => 'أردني',
                        'فلسطيني' => 'فلسطيني',
                        'باكستاني' => 'باكستاني',
                        'هندي' => 'هندي',
                        'بنجلاديشي' => 'بنجلاديشي',
                        'أخرى' => 'أخرى',
                    ])
                    ->multiple(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشط فقط')
                    ->falseLabel('غير نشط فقط'),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('التحقق')
                    ->placeholder('الكل')
                    ->trueLabel('تم التحقق')
                    ->falseLabel('لم يتم التحقق'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIndividualCircleTeachers::route('/'),
            'create' => Pages\CreateIndividualCircleTeacher::route('/create'),
            'view' => Pages\ViewIndividualCircleTeacher::route('/{record}'),
            'edit' => Pages\EditIndividualCircleTeacher::route('/{record}/edit'),
        ];
    }
}
