<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TeacherMosqueScheduleResource\Pages;
use App\Filament\Admin\Resources\TeacherMosqueScheduleResource\RelationManagers;
use App\Filament\Admin\Resources\TeacherMosqueScheduleResource\Widgets;
use App\Models\TeacherMosqueSchedule;
use App\Models\Teacher;
use App\Models\Mosque;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Support\Enums\FontWeight;

class TeacherMosqueScheduleResource extends Resource
{
    protected static ?string $model = TeacherMosqueSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationLabel = 'جداول المعلمين';
    
    protected static ?string $modelLabel = 'جدول معلم';
    
    protected static ?string $pluralModelLabel = 'جداول المعلمين';
    
    protected static ?string $navigationGroup = 'إدارة المساجد';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الجدولة')
                    ->schema([
                        Select::make('teacher_id')
                            ->label('المعلم')
                            ->relationship('teacher', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('اسم المعلم')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->tel()
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->maxLength(255),
                            ]),

                        Select::make('mosque_id')
                            ->label('المسجد')
                            ->relationship('mosque', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('day_of_week')
                            ->label('يوم الأسبوع')
                            ->options([
                                'الأحد' => 'الأحد',
                                'الإثنين' => 'الإثنين',
                                'الثلاثاء' => 'الثلاثاء',
                                'الأربعاء' => 'الأربعاء',
                                'الخميس' => 'الخميس',
                                'الجمعة' => 'الجمعة',
                                'السبت' => 'السبت',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('أوقات العمل')
                    ->schema([
                        TimePicker::make('start_time')
                            ->label('وقت البداية')
                            ->required()
                            ->seconds(false),

                        TimePicker::make('end_time')
                            ->label('وقت النهاية')
                            ->required()
                            ->seconds(false)
                            ->after('start_time'),

                        Select::make('session_type')
                            ->label('نوع الجلسة')
                            ->options([
                                'الفجر' => 'الفجر',
                                'الضحى' => 'الضحى',
                                'الظهر' => 'الظهر',
                                'العصر' => 'العصر',
                                'المغرب' => 'المغرب',
                                'العشاء' => 'العشاء',
                                'أخرى' => 'أخرى',
                            ])
                            ->native(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('معلومات إضافية')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->inline(false),

                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('teacher.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('mosque.name')
                    ->label('المسجد')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('day_of_week')
                    ->label('اليوم')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('time_range')
                    ->label('الوقت')
                    ->getStateUsing(function ($record) {
                        return $record->getFormattedTimeRange();
                    })
                    ->badge()
                    ->color('success'),

                TextColumn::make('session_type')
                    ->label('نوع الجلسة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'الفجر' => 'warning',
                        'الضحى' => 'info',
                        'الظهر' => 'primary',
                        'العصر' => 'success',
                        'المغرب' => 'danger',
                        'العشاء' => 'gray',
                        default => 'secondary',
                    }),

                BooleanColumn::make('is_active')
                    ->label('نشط')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('teacher_id')
                    ->label('المعلم')
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('mosque_id')
                    ->label('المسجد')
                    ->relationship('mosque', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('day_of_week')
                    ->label('يوم الأسبوع')
                    ->options([
                        'الأحد' => 'الأحد',
                        'الإثنين' => 'الإثنين',
                        'الثلاثاء' => 'الثلاثاء',
                        'الأربعاء' => 'الأربعاء',
                        'الخميس' => 'الخميس',
                        'الجمعة' => 'الجمعة',
                        'السبت' => 'السبت',
                    ]),

                SelectFilter::make('session_type')
                    ->label('نوع الجلسة')
                    ->options([
                        'الفجر' => 'الفجر',
                        'الضحى' => 'الضحى',
                        'الظهر' => 'الظهر',
                        'العصر' => 'العصر',
                        'المغرب' => 'المغرب',
                        'العشاء' => 'العشاء',
                        'أخرى' => 'أخرى',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('حالة النشاط')
                    ->placeholder('جميع الجداول')
                    ->trueLabel('النشطة فقط')
                    ->falseLabel('غير النشطة فقط'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('عرض'),
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading('لا توجد جداول معلمين')
            ->emptyStateDescription('ابدأ بإنشاء جدول جديد للمعلمين في المساجد')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\ScheduleStatsOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeacherMosqueSchedules::route('/'),
            'create' => Pages\CreateTeacherMosqueSchedule::route('/create'),
            'view' => Pages\ViewTeacherMosqueSchedule::route('/{record}'),
            'edit' => Pages\EditTeacherMosqueSchedule::route('/{record}/edit'),
        ];
    }
}
