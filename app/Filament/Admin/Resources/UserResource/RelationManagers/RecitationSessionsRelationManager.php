<?php

namespace App\Filament\Admin\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Student;
use App\Models\Circle;

class RecitationSessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'recitationSessions';

    protected static ?string $title = 'جلسات التسميع';

    protected static ?string $modelLabel = 'جلسة تسميع';

    protected static ?string $pluralModelLabel = 'جلسات التسميع';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الجلسة')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->label('الطالب')
                            ->options(Student::all()->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        
                        Forms\Components\Select::make('circle_id')
                            ->label('الحلقة')
                            ->options(Circle::all()->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        
                        Forms\Components\DateTimePicker::make('session_date')
                            ->label('تاريخ ووقت الجلسة')
                            ->required()
                            ->default(now()),
                    ])->columns(3),

                Forms\Components\Section::make('النطاق القرآني')
                    ->schema([
                        Forms\Components\Select::make('surah_start')
                            ->label('السورة الأولى')
                            ->options(function () {
                                $surahs = [];
                                for ($i = 1; $i <= 114; $i++) {
                                    $surahs[$i] = "سورة رقم $i";
                                }
                                return $surahs;
                            })
                            ->required()
                            ->searchable(),
                        
                        Forms\Components\TextInput::make('ayah_start')
                            ->label('الآية الأولى')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        
                        Forms\Components\Select::make('surah_end')
                            ->label('السورة الأخيرة')
                            ->options(function () {
                                $surahs = [];
                                for ($i = 1; $i <= 114; $i++) {
                                    $surahs[$i] = "سورة رقم $i";
                                }
                                return $surahs;
                            })
                            ->required()
                            ->searchable(),
                        
                        Forms\Components\TextInput::make('ayah_end')
                            ->label('الآية الأخيرة')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])->columns(4),

                Forms\Components\Section::make('التقييم')
                    ->schema([
                        Forms\Components\TextInput::make('grade')
                            ->label('الدرجة')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                // Auto-calculate rating based on grade
                                if ($state >= 90) {
                                    $set('rating', 'ممتاز');
                                } elseif ($state >= 80) {
                                    $set('rating', 'جيد جداً');
                                } elseif ($state >= 70) {
                                    $set('rating', 'جيد');
                                } elseif ($state >= 60) {
                                    $set('rating', 'مقبول');
                                } else {
                                    $set('rating', 'ضعيف');
                                }
                            }),
                        
                        Forms\Components\Select::make('rating')
                            ->label('التقدير')
                            ->options([
                                'ممتاز' => 'ممتاز',
                                'جيد جداً' => 'جيد جداً',
                                'جيد' => 'جيد',
                                'مقبول' => 'مقبول',
                                'ضعيف' => 'ضعيف',
                            ])
                            ->required(),
                        
                        Forms\Components\TextInput::make('total_errors')
                            ->label('إجمالي الأخطاء')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Section::make('الملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات المعلم')
                            ->rows(3)
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('recommendations')
                            ->label('التوصيات')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('session_date')
            ->columns([
                Tables\Columns\TextColumn::make('session_date')
                    ->label('تاريخ الجلسة')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('circle.name')
                    ->label('الحلقة')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('surah_range')
                    ->label('النطاق')
                    ->getStateUsing(function ($record) {
                        $start = "سورة {$record->surah_start} آية {$record->ayah_start}";
                        $end = "سورة {$record->surah_end} آية {$record->ayah_end}";
                        return "{$start} - {$end}";
                    }),
                
                Tables\Columns\TextColumn::make('grade')
                    ->label('الدرجة')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state >= 90 => 'success',
                        $state >= 80 => 'info',
                        $state >= 70 => 'warning',
                        $state >= 60 => 'gray',
                        default => 'danger',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('rating')
                    ->label('التقدير')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ممتاز' => 'success',
                        'جيد جداً' => 'info',
                        'جيد' => 'warning',
                        'مقبول' => 'gray',
                        'ضعيف' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('total_errors')
                    ->label('الأخطاء')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'success',
                        $state <= 3 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('student_id')
                    ->label('الطالب')
                    ->options(Student::all()->pluck('name', 'id')),
                
                Tables\Filters\SelectFilter::make('circle_id')
                    ->label('الحلقة')
                    ->options(Circle::all()->pluck('name', 'id')),
                
                Tables\Filters\SelectFilter::make('rating')
                    ->label('التقدير')
                    ->options([
                        'ممتاز' => 'ممتاز',
                        'جيد جداً' => 'جيد جداً',
                        'جيد' => 'جيد',
                        'مقبول' => 'مقبول',
                        'ضعيف' => 'ضعيف',
                    ]),
                
                Tables\Filters\Filter::make('today')
                    ->label('اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('session_date', today())),
                
                Tables\Filters\Filter::make('this_week')
                    ->label('هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('session_date', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ])),
                
                Tables\Filters\Filter::make('this_month')
                    ->label('هذا الشهر')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('session_date', now()->month)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة جلسة تسميع'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
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
            ->defaultSort('session_date', 'desc');
    }
}
