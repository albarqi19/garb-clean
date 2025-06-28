<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StudentCurriculumResource\Pages;
use App\Filament\Admin\Resources\StudentCurriculumResource\RelationManagers;
use App\Models\Curriculum;
use App\Models\CurriculumLevel;
use App\Models\Student;
use App\Models\StudentCurriculum;
use App\Models\Teacher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Components\ProgressColumn;

class StudentCurriculumResource extends Resource
{
    protected static ?string $model = StudentCurriculum::class;    // تعيين أيقونة مناسبة لمناهج الطلاب
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    
    // إضافة الترجمات العربية
    protected static ?string $modelLabel = 'منهج الطالب';
    protected static ?string $pluralModelLabel = 'مناهج الطلاب';
    
    // تعيين اسم الملف (slug) الذي سيستخدم في عنوان URL
    protected static ?string $slug = 'student-curricula';
    
    // تعيين مجموعة التنقل في القائمة
    protected static ?string $navigationGroup = 'التعليمية';
    
    // ترتيب ظهور المورد في القائمة
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->label('الطالب')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->required()
                    ->preload(),
                Forms\Components\Select::make('curriculum_id')
                    ->label('المنهج')
                    ->relationship('curriculum', 'name', fn (Builder $query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $set('curriculum_level_id', null);
                    })
                    ->required(),
                Forms\Components\Select::make('curriculum_level_id')
                    ->label('المستوى')
                    ->options(function (callable $get) {
                        $curriculumId = $get('curriculum_id');
                        if (!$curriculumId) {
                            return [];
                        }
                        
                        $curriculum = Curriculum::find($curriculumId);
                        if (!$curriculum || $curriculum->type !== 'منهج طالب') {
                            return [];
                        }
                        
                        return CurriculumLevel::where('curriculum_id', $curriculumId)
                            ->where('is_active', true)
                            ->orderBy('level_order')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->visible(function (callable $get) {
                        $curriculumId = $get('curriculum_id');
                        if (!$curriculumId) {
                            return false;
                        }
                        
                        $curriculum = Curriculum::find($curriculumId);
                        return $curriculum && $curriculum->type === 'منهج طالب';
                    })
                    ->preload(),
                Forms\Components\Select::make('teacher_id')
                    ->label('المعلم المشرف')
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\DatePicker::make('start_date')
                    ->label('تاريخ البدء')
                    ->required()
                    ->default(now()),
                Forms\Components\DatePicker::make('completion_date')
                    ->label('تاريخ الإكمال')
                    ->nullable(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'قيد التنفيذ' => 'قيد التنفيذ',
                        'مكتمل' => 'مكتمل',
                        'معلق' => 'معلق',
                        'ملغي' => 'ملغي',
                    ])
                    ->required()
                    ->default('قيد التنفيذ'),
                Forms\Components\TextInput::make('completion_percentage')
                    ->label('نسبة الإكمال')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(0)
                    ->suffix('%')
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('curriculum.name')
                    ->label('المنهج')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('curriculum.type')
                    ->label('نوع المنهج')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'منهج تلقين' => 'primary',
                        'منهج طالب' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('level.name')
                    ->label('المستوى')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('المعلم المشرف')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'قيد التنفيذ' => 'info',
                        'مكتمل' => 'success',
                        'معلق' => 'warning',
                        'ملغي' => 'danger',
                        default => 'gray',                    }),
                \App\Filament\Components\ProgressColumn::make('completion_percentage')
                    ->label('نسبة الإكمال')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البدء')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('completion_date')
                    ->label('تاريخ الإكمال')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('curriculum_id')
                    ->label('المنهج')
                    ->relationship('curriculum', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'قيد التنفيذ' => 'قيد التنفيذ',
                        'مكتمل' => 'مكتمل',
                        'معلق' => 'معلق',
                        'ملغي' => 'ملغي',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('view_progress')
                    ->label('عرض التقدم')
                    ->icon('heroicon-s-eye')
                    ->url(fn (StudentCurriculum $record): string => route('filament.admin.resources.student-curricula.progress', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            RelationManagers\ProgressRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentCurricula::route('/'),
            'create' => Pages\CreateStudentCurriculum::route('/create'),
            'edit' => Pages\EditStudentCurriculum::route('/{record}/edit'),
            'progress' => Pages\ViewStudentCurriculumProgress::route('/{record}/progress'),
        ];
    }

    /**
     * إظهار عدد مناهج الطلاب في مربع العدد (Badge) في القائمة
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
        return 'info';
    }
}
