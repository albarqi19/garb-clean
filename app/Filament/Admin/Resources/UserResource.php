<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Filament\Admin\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // تعيين أيقونة مناسبة للمستخدمين
    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    // إضافة الترجمات العربية
    protected static ?string $modelLabel = 'مستخدم';
    protected static ?string $pluralModelLabel = 'المستخدمين';
    
    // وضع المورد في مجموعة النظام
    protected static ?string $navigationGroup = 'إدارة النظام';
    
    // ترتيب المورد في القائمة
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // قسم البيانات الشخصية
                Forms\Components\Section::make('البيانات الشخصية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم الكامل')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('username')
                            ->label('اسم المستخدم')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الجوال')
                            ->tel()
                            ->maxLength(255),
                    ])->columns(2),
                
                // قسم معلومات الحساب والصلاحيات
                Forms\Components\Section::make('معلومات الحساب والصلاحيات')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_active')
                            ->label('حساب نشط')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),
                        Forms\Components\Toggle::make('email_verified_at')
                            ->label('تم التحقق من البريد')
                            ->onColor('success')
                            ->offColor('danger')
                            ->dehydrateStateUsing(fn (bool $state): ?string => $state ? now() : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->default(true),
                        Forms\Components\CheckboxList::make('roles')
                            ->label('الأدوار')
                            ->relationship('roles', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Role $record) => $record->name === 'super_admin' ? 'مدير النظام' :
                                ($record->name === 'admin' ? 'مدير المركز' : 
                                ($record->name === 'supervisor' ? 'المشرف' : 
                                ($record->name === 'teacher' ? 'المعلم' : 
                                ($record->name === 'staff' ? 'الموظف الإداري' : 'الطالب')))))
                            ->columns(2),
                    ]),
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
                Tables\Columns\TextColumn::make('username')
                    ->label('اسم المستخدم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('الدور')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'super_admin' => 'مدير النظام',
                        'admin' => 'مدير المركز',
                        'supervisor' => 'المشرف ',
                        'teacher' => 'المعلم',
                        'staff' => 'الموظف الإداري',
                        'student' => 'الطالب',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'primary',
                        'supervisor' => 'success',
                        'teacher' => 'info',
                        'staff' => 'warning',
                        'student' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الجوال')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('الحسابات النشطة فقط')
                    ->falseLabel('الحسابات المعطلة فقط'),
                Tables\Filters\SelectFilter::make('roles')
                    ->label('تصفية حسب الدور')
                    ->relationship('roles', 'name')
                    ->options([
                        'super_admin' => 'مدير النظام',
                        'admin' => 'مدير المركز',
                        'supervisor' => 'المشرف ',
                        'teacher' => 'المعلم',
                        'staff' => 'الموظف الإداري',
                        'student' => 'الطالب',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\Action::make('تفعيل')
                    ->label('تفعيل')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (User $record): bool => !$record->is_active)
                    ->action(fn (User $record) => $record->update(['is_active' => true])),
                
                Tables\Actions\Action::make('تعطيل')
                    ->label('تعطيل')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->is_active)
                    ->action(fn (User $record) => $record->update(['is_active' => false])),
                
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('تفعيل المحدد')
                        ->color('success')
                        ->icon('heroicon-o-check')
                        ->action(fn (Builder $query) => $query->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('تعطيل المحدد')
                        ->color('danger')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn (Builder $query) => $query->update(['is_active' => false])),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //RelationManagers\RecitationSessionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
