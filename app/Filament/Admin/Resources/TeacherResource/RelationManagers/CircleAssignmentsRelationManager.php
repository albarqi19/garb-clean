<?php

namespace App\Filament\Admin\Resources\TeacherResource\RelationManagers;

use App\Models\QuranCircle;
use App\Models\TeacherCircleAssignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

class CircleAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'circleAssignments';

    protected static ?string $title = 'تكليفات الحلقات';
    protected static ?string $modelLabel = 'تكليف';
    protected static ?string $pluralModelLabel = 'تكليفات الحلقات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('quran_circle_id')
                    ->label('الحلقة القرآنية')
                    ->relationship('circle', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        // التحقق من تعارض الأوقات عند تغيير الحلقة
                        $teacherId = $this->getOwnerRecord()->id;
                        if ($state && $teacherId) {
                            $circle = QuranCircle::find($state);
                            if ($circle) {
                                $startDate = $get('start_date') ?? now();
                                $endDate = $get('end_date');
                                
                                if (TeacherCircleAssignment::hasTimeConflict($teacherId, $state, $startDate, $endDate)) {
                                    $set('quran_circle_id', null);
                                    // يمكن إضافة notification هنا
                                    \Filament\Notifications\Notification::make()
                                        ->title('تعارض في الأوقات')
                                        ->body('هذا المعلم مكلف بحلقة أخرى في نفس الفترة الزمنية')
                                        ->danger()
                                        ->send();
                                }
                            }
                        }
                    }),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true)
                        ->required(),

                    Forms\Components\DatePicker::make('start_date')
                        ->label('تاريخ البداية')
                        ->default(now())
                        ->required()
                        ->reactive(),

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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('circle.name')
            ->columns([
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

                SelectFilter::make('quran_circle_id')
                    ->label('الحلقة')
                    ->relationship('circle', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('active_assignments')
                    ->label('التكليفات النشطة فقط')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),

                Filter::make('recent_assignments')
                    ->label('التكليفات الأخيرة (30 يوم)')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30))),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة تكليف جديد')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['teacher_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                    
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                    
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

                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                        
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
}
