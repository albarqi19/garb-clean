<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TeacherResource\Pages;
use App\Filament\Admin\Resources\TeacherResource\RelationManagers;
use App\Models\Teacher;
use App\Models\Mosque;
use App\Models\QuranCircle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    // تعيين أيقونة مناسبة للمعلمين
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    // إضافة الترجمات العربية
    protected static ?string $modelLabel = 'معلم';
    protected static ?string $pluralModelLabel = 'المعلمين';
      // تعيين مجموعة التنقل في القائمة
    protected static ?string $navigationGroup = 'إدارة الطلاب والمعلمين';
    
    // ترتيب ظهور المورد في القائمة
    protected static ?int $navigationSort = 22;

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
                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                
                // قسم بيانات العمل
                Forms\Components\Section::make('بيانات العمل')
                    ->schema([
                        Forms\Components\Select::make('mosque_id')
                            ->label('المسجد')
                            ->relationship('mosque', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('اسم المسجد')
                                    ->required(),
                                Forms\Components\TextInput::make('neighborhood')
                                    ->label('الحي')
                                    ->required(),
                            ]),                        Forms\Components\Select::make('quran_circle_id')
                            ->label('الحلقة القرآنية')
                            ->relationship('quranCircle', 'name')
                            ->searchable()
                            ->preload()
                            ->afterStateUpdated(function ($set, $state) {
                                if ($state) {
                                    $circle = QuranCircle::find($state);
                                    if ($circle) {
                                        // تعيين نوع الحلقة ونوع المهمة بناءً على نوع الحلقة القرآنية
                                        $set('circle_type', $circle->circle_type);
                                        
                                        // إذا كانت المدرسة من نوع حلقة جماعية، نعين المعلم إلى مشرف حلقة
                                        if ($circle->circle_type === 'حلقة جماعية') {
                                            $set('job_title', 'مشرف');
                                        }
                                        
                                        // تعيين المسجد أيضاً إذا لم يكن معيناً
                                        if ($circle->mosque_id) {
                                            $set('mosque_id', $circle->mosque_id);
                                        }
                                    }
                                }
                            }),Forms\Components\Select::make('job_title')
                            ->label('المسمى الوظيفي')
                            ->options([
                                'معلم حفظ' => 'معلم حفظ',
                                'معلم تلقين' => 'معلم تلقين',
                                'مشرف مقيم' => 'مشرف مقيم',
                                'مساعد مشرف مقيم' => 'مساعد مشرف مقيم',
                                'مشرف' => 'مشرف',
                            ])
                            ->required(),
                        Forms\Components\Select::make('task_type')
                            ->label('نوع المهمة')
                            ->options([
                                'معلم بمكافأة' => 'معلم بمكافأة',
                                'معلم محتسب' => 'معلم محتسب',
                                'مشرف' => 'مشرف',
                                'مساعد مشرف' => 'مساعد مشرف',
                            ])
                            ->required(),
                        Forms\Components\Select::make('circle_type')
                            ->label('نوع الحلقة')
                            ->options([
                                'حلقة فردية' => 'حلقة فردية',
                                'حلقة جماعية' => 'حلقة جماعية',
                            ])
                            ->required(),                        Forms\Components\Select::make('work_time')
                            ->label('وقت العمل')
                            ->options([
                                'عصر' => 'فترة العصر',
                                'مغرب' => 'فترة المغرب',
                                'عصر ومغرب' => 'فترة العصر والمغرب',
                                'كل الأوقات' => 'كل الأوقات',
                                'جميع الفترات' => 'جميع الفترات',
                            ])
                            ->required(),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ بداية العمل'),
                        Forms\Components\TextInput::make('system_number')
                            ->label('الرقم في النظام')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                
                // قسم البيانات المالية والتقييم
                Forms\Components\Section::make('البيانات المالية والتقييم')
                    ->schema([
                        Forms\Components\TextInput::make('iban')
                            ->label('رقم الآيبان')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('ratel_activated')
                            ->label('تفعيل راتل')
                            ->onColor('success')
                            ->offColor('danger'),
                        Forms\Components\TextInput::make('absence_count')
                            ->label('عدد أيام الغياب')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),                        Forms\Components\TextInput::make('evaluation')
                            ->label('التقييم')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%'),
                    ])
                    ->columns(2),
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
                Tables\Columns\TextColumn::make('nationality')
                    ->label('الجنسية')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('task_type')
                    ->label('نوع المهمة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'معلم بمكافأة' => 'success',
                        'معلم محتسب' => 'info',
                        'مشرف' => 'primary',
                        'مساعد مشرف' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('mosque.name')
                    ->label('المسجد')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('circle_type')
                    ->label('نوع الحلقة')
                    ->badge(),
                Tables\Columns\TextColumn::make('work_time')
                    ->label('وقت العمل'),
                Tables\Columns\TextColumn::make('quranCircle.name')
                    ->label('الحلقة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('ratel_activated')
                    ->label('راتل مفعل')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark'),
                Tables\Columns\TextColumn::make('evaluation')
                    ->label('التقييم')
                    ->numeric()
                    ->sortable()
                    ->suffix('%')
                    ->color(fn ($state) => match (true) {
                        $state >= 90 => 'success',
                        $state >= 75 => 'primary',
                        $state >= 60 => 'warning',
                        default => 'danger',
                    }),                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البداية')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active_user')
                    ->label('نشط')
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
                Tables\Filters\SelectFilter::make('task_type')
                    ->label('نوع المهمة')
                    ->options([
                        'معلم بمكافأة' => 'معلم بمكافأة',
                        'معلم محتسب' => 'معلم محتسب',
                        'مشرف' => 'مشرف',
                        'مساعد مشرف' => 'مساعد مشرف',
                    ]),
                Tables\Filters\SelectFilter::make('mosque_id')
                    ->label('المسجد')
                    ->relationship('mosque', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('circle_type')
                    ->label('نوع الحلقة')
                    ->options([
                        'حلقة فردية' => 'حلقة فردية',
                        'حلقة جماعية' => 'حلقة جماعية',
                    ]),
                Tables\Filters\Filter::make('ratel_activated')
                    ->label('راتل مفعل')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('ratel_activated', true)),
            ])            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                // إضافة زر لإنشاء حساب مستخدم للمعلم
                Tables\Actions\Action::make('createUserAccount')
                    ->label('إنشاء حساب مستخدم')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn (Teacher $record): bool => in_array($record->task_type, ['مشرف', 'مساعد مشرف']) && !empty($record->identity_number))
                    ->requiresConfirmation()
                    ->action(function (Teacher $record): void {
                        // التحقق من وجود المستخدم بنفس رقم الهوية
                        $existingUser = \App\Models\User::where('identity_number', $record->identity_number)->first();
                        
                        if ($existingUser) {
                            // إذا كان المستخدم موجوداً بالفعل، قم بتعيين دور المشرف فقط
                            $existingUser->assignRole('supervisor');
                            
                            // عرض رسالة للمستخدم
                            \Filament\Notifications\Notification::make()
                                ->title('تم تحديث بيانات المستخدم')
                                ->body("تم العثور على حساب موجود بالفعل للمعلم {$record->name} وتم تعيين دور المشرف له.")
                                ->success()
                                ->send();
                                
                            return;
                        }
                        
                        // إنشاء حساب مستخدم جديد
                        $user = \App\Models\User::create([
                            'name' => $record->name,
                            'email' => $record->identity_number . '@example.com', // إنشاء بريد إلكتروني مؤقت
                            'username' => $record->identity_number, // استخدام رقم الهوية كاسم مستخدم
                            'password' => \Illuminate\Support\Facades\Hash::make($record->identity_number), // استخدام رقم الهوية ككلمة مرور مبدئية
                            'phone' => $record->phone,
                            'identity_number' => $record->identity_number,
                            'is_active' => true,
                            'email_verified_at' => now(),
                        ]);
                        
                        // تعيين دور المشرف للمستخدم
                        $user->assignRole('supervisor');
                        
                        // عرض رسالة للمستخدم
                        \Filament\Notifications\Notification::make()
                            ->title('تم إنشاء حساب مستخدم')
                            ->body("تم إنشاء حساب مستخدم للمعلم {$record->name} بنجاح مع دور المشرف.")
                            ->success()
                            ->send();
                    }),
                // إضافة زر لإرسال كلمة المرور عبر واتساب
                Tables\Actions\Action::make('sendPassword')
                    ->label('إرسال كلمة المرور')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->visible(fn (Teacher $record): bool => $record->phone && $record->plain_password)
                    ->requiresConfirmation()
                    ->modalHeading('إرسال كلمة المرور عبر واتساب')
                    ->modalDescription(fn (Teacher $record): string => "هل تريد إرسال كلمة المرور للمعلم {$record->name} على رقم {$record->phone}؟")
                    ->action(function (Teacher $record): void {
                        $sent = $record->sendWelcomeWithPassword();
                        
                        if ($sent) {
                            \Filament\Notifications\Notification::make()
                                ->title('تم إرسال كلمة المرور')
                                ->body("تم إرسال كلمة المرور للمعلم {$record->name} عبر واتساب على رقم {$record->phone}")
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('فشل في الإرسال')
                                ->body("لم يتم إرسال كلمة المرور للمعلم {$record->name}")
                                ->danger()
                                ->send();
                        }
                    }),
                // إضافة زر لإعادة تعيين كلمة مرور جديدة وإرسالها
                Tables\Actions\Action::make('resetPassword')
                    ->label('إعادة تعيين كلمة المرور')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->visible(fn (Teacher $record): bool => !empty($record->phone))
                    ->requiresConfirmation()
                    ->modalHeading('إعادة تعيين كلمة المرور')
                    ->modalDescription(fn (Teacher $record): string => "هل تريد إعادة تعيين كلمة مرور جديدة للمعلم {$record->name} وإرسالها عبر واتساب؟")
                    ->action(function (Teacher $record): void {
                        // توليد كلمة مرور جديدة
                        $newPassword = Teacher::generateRandomPassword();
                        
                        // تحديث كلمة المرور
                        $record->password = $newPassword;
                        $record->must_change_password = true;
                        $record->save();
                        
                        // إرسال كلمة المرور الجديدة
                        $sent = $record->sendPasswordViaWhatsApp($newPassword);
                        
                        if ($sent) {
                            \Filament\Notifications\Notification::make()
                                ->title('تم إعادة تعيين كلمة المرور')
                                ->body("تم إعادة تعيين كلمة مرور جديدة للمعلم {$record->name} وإرسالها عبر واتساب")
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('تم إعادة التعيين لكن فشل الإرسال')
                                ->body("تم إعادة تعيين كلمة المرور لكن فشل إرسالها عبر واتساب للمعلم {$record->name}")
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }    public static function getRelations(): array
    {
        return [
            RelationManagers\CircleAssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeachers::route('/'),
            'create' => Pages\CreateTeacher::route('/create'),
            'edit' => Pages\EditTeacher::route('/{record}/edit'),
        ];
    }

    /**
     * إظهار عدد المعلمين في مربع العدد (Badge) في القائمة
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
        return 'success';
    }
}
