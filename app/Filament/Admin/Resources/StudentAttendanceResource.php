<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StudentAttendanceResource\Pages;
use App\Filament\Admin\Resources\StudentAttendanceResource\RelationManagers;
use App\Models\Student;
use App\Models\StudentAttendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;

class StudentAttendanceResource extends Resource
{
    protected static ?string $model = StudentAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationLabel = 'حضور الطلاب';
    
    protected static ?string $modelLabel = 'حضور طالب';
    
    protected static ?string $pluralModelLabel = 'حضور الطلاب';
    
    protected static ?string $navigationGroup = 'إدارة الطلاب';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->label('الطالب')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(1),
                    
                Forms\Components\DatePicker::make('date')
                    ->label('التاريخ')
                    ->required()
                    ->default(now())
                    ->columnSpan(1),
                    
                Forms\Components\Select::make('status')
                    ->label('حالة الحضور')
                    ->options([
                        'حاضر' => 'حاضر',
                        'غائب' => 'غائب',
                        'متأخر' => 'متأخر',
                        'مأذون' => 'مأذون'
                    ])
                    ->required()
                    ->default('حاضر')
                    ->columnSpan(1),
                    
                Forms\Components\TextInput::make('period')
                    ->label('الحصة/الفترة')
                    ->maxLength(50)
                    ->columnSpan(1),
                    
                Forms\Components\Textarea::make('excuse_reason')
                    ->label('سبب العذر')
                    ->maxLength(500)
                    ->rows(3)
                    ->columnSpanFull()
                    ->visible(fn (Forms\Get $get) => $get('status') === 'مأذون'),
                    
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->maxLength(1000)
                    ->rows(3)
                    ->columnSpanFull(),
                    
                Forms\Components\TextInput::make('recorded_by')
                    ->label('تم التسجيل بواسطة')
                    ->maxLength(255)
                    ->default('Admin')
                    ->columnSpan(1),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('اسم الطالب')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('date')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'حاضر' => 'حاضر',
                        'غائب' => 'غائب',
                        'متأخر' => 'متأخر',
                        'مأذون' => 'مأذون',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'حاضر',
                        'danger' => 'غائب',
                        'warning' => 'متأخر',
                        'info' => 'مأذون',
                    ]),
                    
                Tables\Columns\TextColumn::make('period')
                    ->label('الحصة/الفترة')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('excuse_reason')
                    ->label('سبب العذر')
                    ->limit(30)
                    ->toggleable()
                    ->visible(fn ($record) => $record && $record->status === 'مأذون'),
                    
                Tables\Columns\TextColumn::make('recorded_by')
                    ->label('المسجل')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('وقت التسجيل')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('حالة الحضور')
                    ->options([
                        'حاضر' => 'حاضر',
                        'غائب' => 'غائب',
                        'متأخر' => 'متأخر',
                        'مأذون' => 'مأذون'
                    ]),
                    
                SelectFilter::make('student')
                    ->label('الطالب')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload(),
                    
                Filter::make('date_range')
                    ->label('فترة التاريخ')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
                    
                Filter::make('today')
                    ->label('اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('date', today()))
                    ->toggle(),
                    
                Filter::make('this_week')
                    ->label('هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('date', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]))
                    ->toggle(),
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
                ]),
            ])
            ->defaultSort('date', 'desc');
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
            'index' => Pages\ListStudentAttendances::route('/'),
            'create' => Pages\CreateStudentAttendance::route('/create'),
            'edit' => Pages\EditStudentAttendance::route('/{record}/edit'),
        ];
    }
}
