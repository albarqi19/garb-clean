<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TeacherCircleAssignmentResource\Pages;
use App\Filament\Admin\Resources\TeacherCircleAssignmentResource\RelationManagers;
use App\Models\TeacherCircleAssignment;
use App\Models\Teacher;
use App\Models\QuranCircle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

class TeacherCircleAssignmentResource extends Resource
{
    protected static ?string $model = TeacherCircleAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'تكليفات المعلمين';

    protected static ?string $modelLabel = 'تكليف معلم';

    protected static ?string $pluralModelLabel = 'تكليفات المعلمين';

    protected static ?string $navigationGroup = 'إدارة الحلقات';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)->schema([
                    Forms\Components\Select::make('teacher_id')
                        ->label('المعلم')
                        ->relationship('teacher', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            // التحقق من تعارض الأوقات عند تغيير المعلم
                            $circleId = $get('quran_circle_id');
                            if ($state && $circleId) {
                                $teacher = Teacher::find($state);
                                $circle = QuranCircle::find($circleId);
                                if ($teacher && $circle) {
                                    $startDate = $get('start_date') ?? now();
                                $endDate = $get('end_date');
                                    
                                if (TeacherCircleAssignment::hasTimeConflict($state, $circleId, $startDate, $endDate)) {
                                    $set('teacher_id', null);
                                    \Filament\Notifications\Notification::make()
                                        ->title('تعارض في الأوقات')
                                        ->body('هذا المعلم مكلف بحلقة أخرى في نفس الفترة الزمنية')
                                        ->danger()
                                        ->send();
                                }
                                }
                            }
                        }),

                    Forms\Components\Select::make('quran_circle_id')
                        ->label('الحلقة القرآنية')
                        ->relationship('circle', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                ]),

                Grid::make(3)->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true)
                        ->required(),

                    Forms\Components\DatePicker::make('start_date')
                        ->label('تاريخ البداية')
                        ->default(now())
                        ->required()
                        ->live(),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('تاريخ النهاية')
                        ->afterOrEqual('start_date'),
                ]),

                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('circle.name')
                    ->label('الحلقة القرآنية')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('circle.mosque.name')
                    ->label('المسجد')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البداية')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('تاريخ النهاية')
                    ->date('Y-m-d')
                    ->placeholder('مستمر')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('المدة')
                    ->getStateUsing(function (TeacherCircleAssignment $record): string {
                        $start = $record->start_date;
                        $end = $record->end_date ?? now();
                        $days = $start->diffInDays($end);
                        
                        if ($days < 30) {
                            return $days . ' يوم';
                        } elseif ($days < 365) {
                            return round($days / 30) . ' شهر';
                        } else {
                            return round($days / 365, 1) . ' سنة';
                        }
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('الحالة')
                    ->options([
                        true => 'نشط',
                        false => 'غير نشط',
                    ]),

                SelectFilter::make('teacher_id')
                    ->label('المعلم')
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('quran_circle_id')
                    ->label('الحلقة')
                    ->relationship('circle', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('active_assignments')
                    ->label('التكليفات النشطة فقط')
                    ->query(fn (Builder $query): Builder => $query->active()),

                Filter::make('recent_assignments')
                    ->label('التكليفات الأخيرة (30 يوم)')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('deactivate')
                    ->label('إلغاء التكليف')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (TeacherCircleAssignment $record): bool => $record->is_active)
                    ->action(function (TeacherCircleAssignment $record): void {
                        $record->update([
                            'is_active' => false,
                            'end_date' => now(),
                        ]);
                    }),
                Tables\Actions\Action::make('reactivate')
                    ->label('إعادة التفعيل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (TeacherCircleAssignment $record): bool => !$record->is_active)
                    ->action(function (TeacherCircleAssignment $record): void {
                        $record->update([
                            'is_active' => true,
                            'end_date' => null,
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('deactivate_selected')
                        ->label('إلغاء التكليفات المحددة')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                $record->update([
                                    'is_active' => false,
                                    'end_date' => now(),
                                ]);
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListTeacherCircleAssignments::route('/'),
            'create' => Pages\CreateTeacherCircleAssignment::route('/create'),
            'view' => Pages\ViewTeacherCircleAssignment::route('/{record}'),
            'edit' => Pages\EditTeacherCircleAssignment::route('/{record}/edit'),
        ];
    }
}
