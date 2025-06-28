<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StudentResource\Pages;
use App\Filament\Admin\Resources\StudentResource\RelationManagers;
use App\Models\Student;
use App\Models\Mosque;
use App\Models\QuranCircle;
use App\Models\CircleGroup;
use App\Enums\Nationality;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    // تعيين أيقونة مناسبة للطلاب
    protected static ?string $navigationIcon = 'heroicon-o-user';
    
    // إضافة الترجمات العربية
    protected static ?string $modelLabel = 'طالب';
    protected static ?string $pluralModelLabel = 'الطلاب';
      // تعيين مجموعة التنقل في القائمة
    protected static ?string $navigationGroup = 'إدارة الطلاب والمعلمين';
    
    // ترتيب ظهور المورد في القائمة
    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // قسم البيانات الشخصية
                Forms\Components\Section::make('البيانات الشخصية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(255),                        Forms\Components\TextInput::make('identity_number')
                            ->label('رقم الهوية')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('nationality')
                            ->label('الجنسية')
                            ->required()
                            ->options([
                                'سعودي' => 'سعودي',
                                'مصري' => 'مصري',
                                'سوداني' => 'سوداني',
                                'يمني' => 'يمني',
                                'أردني' => 'أردني',
                                'سوري' => 'سوري',
                                'فلسطيني' => 'فلسطيني',
                                'باكستاني' => 'باكستاني',
                                'هندي' => 'هندي',
                                'بنغلاديشي' => 'بنغلاديشي',
                                'أخرى' => 'أخرى',
                            ])
                            ->searchable(),
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('تاريخ الميلاد')
                            ->format('Y-m-d')
                            ->displayFormat('Y-m-d')
                            ->maxDate(now()),
                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('education_level')
                            ->label('المستوى التعليمي')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                  // قسم بيانات الحلقة والمسجد
                Forms\Components\Section::make('بيانات الحلقة والمسجد')
                    ->schema([                        Forms\Components\Select::make('quran_circle_id')
                            ->label('الحلقة القرآنية')
                            ->options(function () {
                                return QuranCircle::pluck('name', 'id')->toArray();
                            })
                            ->live()
                            ->searchable()
                            ->afterStateUpdated(function ($set) {
                                $set('circle_group_id', null);
                            }),Forms\Components\Select::make('circle_group_id')
                            ->label('الحلقة الفرعية')
                            ->options(function (callable $get) {
                                $quranCircleId = $get('quran_circle_id');
                                if (!$quranCircleId) {
                                    return [];
                                }
                                
                                $quranCircle = \App\Models\QuranCircle::find($quranCircleId);
                                if (!$quranCircle || $quranCircle->circle_type !== 'حلقة جماعية') {
                                    return [];
                                }
                                
                                return \App\Models\CircleGroup::where('quran_circle_id', $quranCircleId)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->visible(fn (callable $get) => 
                                $get('quran_circle_id') && 
                                \App\Models\QuranCircle::find($get('quran_circle_id'))?->circle_type === 'حلقة جماعية'
                            ),                        Forms\Components\Select::make('mosque_id')
                            ->label('المسجد')
                            ->options(function () {
                                return Mosque::pluck('name', 'id')->toArray();
                            })
                            ->searchable(),
                        Forms\Components\TextInput::make('neighborhood')
                            ->label('الحي')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('enrollment_date')
                            ->label('تاريخ الالتحاق')
                            ->format('Y-m-d')
                            ->displayFormat('Y-m-d')
                            ->default(now()),
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(true),
                    ])
                    ->columns(2),
                
                // قسم بيانات الحفظ والمراجعة
                Forms\Components\Section::make('بيانات الحفظ والمراجعة')
                    ->schema([
                        Forms\Components\TextInput::make('parts_count')
                            ->label('عدد الأجزاء المحفوظة')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(30)
                            ->suffix('جزء'),
                        Forms\Components\TextInput::make('absence_count')
                            ->label('عدد أيام الغياب')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        Forms\Components\TextInput::make('last_exam')
                            ->label('آخر اختبار')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('memorization_plan')
                            ->label('خطة الحفظ')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('review_plan')
                            ->label('خطة المراجعة')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                
                // قسم بيانات ولي الأمر
                Forms\Components\Section::make('بيانات ولي الأمر')
                    ->schema([
                        Forms\Components\TextInput::make('guardian_name')
                            ->label('اسم ولي الأمر')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('guardian_phone')
                            ->label('رقم هاتف ولي الأمر')
                            ->tel()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                
                // قسم الملاحظات
                Forms\Components\Section::make('الملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('teacher_notes')
                            ->label('ملاحظات المعلم')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->columnSpanFull(),                        Forms\Components\Textarea::make('center_notes')
                            ->label('ملاحظات المركز')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
                  // قسم إعدادات تسجيل الدخول
                Forms\Components\Section::make('إعدادات تسجيل الدخول')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->placeholder('سيتم توليد كلمة مرور تلقائياً إذا تُركت فارغة')
                            ->helperText('إذا تُركت فارغة، سيتم توليد كلمة مرور عشوائية تلقائياً'),
                        Forms\Components\TextInput::make('plain_password')
                            ->label('كلمة المرور الأصلية')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('ستظهر هنا كلمة المرور المولدة أو المدخلة'),
                        Forms\Components\Toggle::make('must_change_password')
                            ->label('يجب تغيير كلمة المرور عند أول تسجيل دخول')
                            ->default(true)
                            ->onColor('warning')
                            ->offColor('success'),
                        Forms\Components\Toggle::make('is_active_user')
                            ->label('المستخدم نشط')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),
                        Forms\Components\DateTimePicker::make('last_login_at')
                            ->label('آخر تسجيل دخول')
                            ->disabled()
                            ->displayFormat('d/m/Y H:i')
                            ->placeholder('لم يسجل دخول بعد'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('identity_number')
                    ->label('رقم الهوية')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('mosque.name')
                    ->label('المسجد')
                    ->searchable()
                    ->sortable(),                Tables\Columns\TextColumn::make('quranCircle.name')
                    ->label('المدرسة القرآنية')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('circleGroup.name')
                    ->label('الحلقة الفرعية')
                    ->searchable()
                    ->sortable()
                    ->visible(fn ($record) => $record?->quranCircle?->circle_type === 'حلقة جماعية'),
                Tables\Columns\TextColumn::make('age')
                    ->label('العمر')
                    ->numeric()
                    ->sortable()
                    ->suffix(' سنة'),
                Tables\Columns\TextColumn::make('parts_count')
                    ->label('عدد الأجزاء')
                    ->numeric()
                    ->sortable()
                    ->suffix(' جزء'),
                Tables\Columns\TextColumn::make('enrollment_period')
                    ->label('مدة الالتحاق')
                    ->searchable(),
                Tables\Columns\TextColumn::make('absence_count')
                    ->label('الغياب')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher_name')
                    ->label('المعلم')
                    ->searchable(),                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\IconColumn::make('is_active_user')
                    ->label('مستخدم نشط')
                    ->boolean()
                    ->trueIcon('heroicon-o-user-circle')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('must_change_password')
                    ->label('يجب تغيير كلمة المرور')
                    ->boolean()
                    ->trueIcon('heroicon-o-key')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->toggleable(),                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('آخر تسجيل دخول')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('لم يسجل دخول'),
                Tables\Columns\TextColumn::make('plain_password')
                    ->label('كلمة المرور')
                    ->copyable()
                    ->copyMessage('تم نسخ كلمة المرور')
                    ->toggleable()
                    ->placeholder('غير محدد')
                    ->badge()
                    ->color('info'),
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
                // تصفية حسب النشاط
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('جميع الطلاب')
                    ->trueLabel('الطلاب النشطين')
                    ->falseLabel('الطلاب غير النشطين'),
                
                // تصفية حسب المسجد
                Tables\Filters\SelectFilter::make('mosque_id')
                    ->label('المسجد')
                    ->relationship('mosque', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                  // تصفية حسب الحلقة
                Tables\Filters\SelectFilter::make('quran_circle_id')
                    ->label('المدرسة القرآنية')
                    ->relationship('quranCircle', 'name')
                    ->searchable()
                    ->preload(),
                
                // تصفية حسب الحلقة الفرعية  
                Tables\Filters\SelectFilter::make('circle_group_id')
                    ->label('الحلقة الفرعية')
                    ->relationship('circleGroup', 'name')
                    ->searchable()
                    ->preload(),
                
                // تصفية حسب عدد الأجزاء المحفوظة
                Tables\Filters\Filter::make('parts_count')
                    ->form([
                        Forms\Components\TextInput::make('parts_min')
                            ->label('الحد الأدنى للأجزاء')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(30),
                        Forms\Components\TextInput::make('parts_max')
                            ->label('الحد الأقصى للأجزاء')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(30),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['parts_min'],
                                fn (Builder $query, $value): Builder => $query->where('parts_count', '>=', $value),
                            )
                            ->when(
                                $data['parts_max'],
                                fn (Builder $query, $value): Builder => $query->where('parts_count', '<=', $value),
                            );
                    })
            ])            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (Student $record) => $record->is_active ? 'تعطيل' : 'تنشيط')
                    ->icon(fn (Student $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Student $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Student $record): void {
                        $record->update([
                            'is_active' => !$record->is_active,
                        ]);
                    }),
                Tables\Actions\Action::make('addToCurriculum')
                    ->label('إضافة إلى منهج')
                    ->icon('heroicon-o-book-open')
                    ->color('primary')
                    ->url(fn (Student $record): string => route('filament.admin.resources.student-curricula.create', ['student_id' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activateBulk')
                        ->label('تنشيط')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Builder $query) => $query->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivateBulk')
                        ->label('تعطيل')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (Builder $query) => $query->update(['is_active' => false])),
                ]),
            ])
            ->defaultSort('name');
    }    public static function getRelations(): array
    {
        return [
            RelationManagers\CurriculaRelationManager::class,
            //RelationManagers\RecitationSessionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }

    // تصفية الطلاب النشطين تلقائيًا
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * إظهار عدد الطلاب في مربع العدد (Badge) في القائمة
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
        return 'primary';
    }
}
