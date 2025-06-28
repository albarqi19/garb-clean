<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RoleResource\Pages;
use App\Filament\Admin\Resources\RoleResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    // تعيين أيقونة مناسبة للأدوار
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    // إضافة الترجمات العربية
    protected static ?string $modelLabel = 'دور';
    protected static ?string $pluralModelLabel = 'الأدوار والصلاحيات';
    
    // وضع المورد في مجموعة النظام
    protected static ?string $navigationGroup = 'إدارة النظام';
    
    // ترتيب المورد في القائمة
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        // الحصول على قائمة بجميع الصلاحيات مرتبة حسب المجموعات
        $permissionGroups = [
            'users' => 'المستخدمين',
            'circles' => 'الحلقات القرآنية',
            'mosques' => 'المساجد',
            'students' => 'الطلاب',
            'teachers' => 'المعلمين',
            'attendance' => 'الحضور والغياب',
            'memorization' => 'التسميع',
            'employees' => 'الموظفين',
            'finance' => 'المالية',
            'reports' => 'التقارير',
            'settings' => 'الإعدادات',
        ];
        
        $permissionSections = [];
        
        // إنشاء قسم لكل مجموعة صلاحيات
        foreach ($permissionGroups as $groupName => $groupLabel) {
            $permissions = Permission::where('name', 'like', "%{$groupName}%")->get();
            if ($permissions->count() > 0) {
                $permissionSections[] = Forms\Components\Section::make("صلاحيات {$groupLabel}")
                    ->description("إدارة صلاحيات {$groupLabel}")
                    ->schema([
                        Forms\Components\CheckboxList::make("permissions_{$groupName}")
                            ->label('')
                            ->relationship('permissions', 'name')
                            ->options($permissions->pluck('name', 'id')->toArray())
                            ->bulkToggleable()
                            ->columns(2)
                            ->gridDirection('row')
                    ]);
            }
        }
        
        return $form
            ->schema([
                // قسم معلومات الدور
                Forms\Components\Section::make('معلومات الدور')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الدور')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('guard_name')
                            ->label('اسم الحارس')
                            ->default('web')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                
                // أقسام الصلاحيات
                ...$permissionSections,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الدور')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'super_admin' => 'مدير النظام',
                        'admin' => 'مدير المركز',
                        'supervisor' => 'المشرف',
                        'teacher' => 'المعلم',
                        'staff' => 'الموظف الإداري',
                        'student' => 'الطالب',
                        default => $state,
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('عدد الصلاحيات')
                    ->counts('permissions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('عدد المستخدمين')
                    ->counts('users')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
