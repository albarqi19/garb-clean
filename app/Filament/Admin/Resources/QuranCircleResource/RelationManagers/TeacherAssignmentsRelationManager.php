<?php

namespace App\Filament\Admin\Resources\QuranCircleResource\RelationManagers;

use App\Models\Teacher;
use App\Models\TeacherCircleAssignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TeacherAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'teacherAssignments';
    protected static ?string $title = 'المعلمين المكلفين';
    protected static ?string $label = 'تكليف معلم';
    protected static ?string $pluralLabel = 'تكليفات المعلمين';

    public function form(Form $form): Form
    {
        return $form
            ->schema([                Forms\Components\Select::make('teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $currentCircle = $this->getOwnerRecord();
                        
                        // الحصول على جميع المعلمين المتاحين (بدون تعارض أوقات)
                        return Teacher::whereDoesntHave('activeCircleAssignments', function ($query) use ($currentCircle) {
                            $query->whereHas('circle', function ($circleQuery) use ($currentCircle) {
                                // التحقق من تعارض الأوقات
                                $circleQuery->where('time_period', $currentCircle->time_period)
                                           ->where('id', '!=', $currentCircle->id);
                            });
                        })->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->helperText('يظهر فقط المعلمين المتاحين بدون تعارض أوقات')
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if ($state) {
                            $quranCircleId = $get('../../id') ?? $this->getOwnerRecord()->id;
                            
                            // التحقق من وجود تكليف نشط مسبق في نفس الحلقة
                            $existingAssignment = TeacherCircleAssignment::where('teacher_id', $state)
                                ->where('quran_circle_id', $quranCircleId)
                                ->where('is_active', true)
                                ->first();
                                
                            if ($existingAssignment) {
                                Notification::make()
                                    ->warning()
                                    ->title('تحذير')
                                    ->body('هذا المعلم مكلف بالفعل في هذه الحلقة')
                                    ->send();
                                    
                                $set('teacher_id', null); // إلغاء الاختيار
                            }
                        }
                    }),
                    
                Forms\Components\DatePicker::make('start_date')
                    ->label('تاريخ البداية')
                    ->required()
                    ->default(now())
                    ->format('Y-m-d'),
                    
                Forms\Components\DatePicker::make('end_date')
                    ->label('تاريخ النهاية')
                    ->format('Y-m-d')
                    ->after('start_date'),
                    
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
                    
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('teacher.name')
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('teacher.phone')
                    ->label('الهاتف')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('teacher.nationality')
                    ->label('الجنسية')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'سعودي' => 'success',
                        'مصري' => 'info',
                        'سوداني' => 'warning',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البداية')
                    ->date('d/m/Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('end_date')
                    ->label('تاريخ النهاية')
                    ->date('d/m/Y')
                    ->placeholder('مستمر')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                Tables\Columns\TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->limit(50)
                    ->tooltip(function (TeacherCircleAssignment $record): ?string {
                        return $record->notes;
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشط فقط')
                    ->falseLabel('غير نشط فقط'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة تكليف معلم')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['quran_circle_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    })
                    ->before(function (array $data) {
                        // التحقق من التعارض قبل الحفظ
                        $teacher = Teacher::find($data['teacher_id']);
                        $circle = $this->getOwnerRecord();
                        
                        if ($teacher && $teacher->hasTimeConflictWith($circle)) {
                            Notification::make()
                                ->danger()
                                ->title('لا يمكن إضافة التكليف')
                                ->body('يوجد تعارض في أوقات العمل بين المعلم والحلقة')
                                ->persistent()
                                ->send();
                                
                            $this->halt();
                        }
                        
                        // التحقق من وجود تكليف نشط
                        $existingAssignment = TeacherCircleAssignment::where('teacher_id', $data['teacher_id'])
                            ->where('quran_circle_id', $circle->id)
                            ->where('is_active', true)
                            ->first();
                            
                        if ($existingAssignment) {
                            Notification::make()
                                ->warning()
                                ->title('تحذير')
                                ->body('هذا المعلم مكلف بالفعل في هذه الحلقة')
                                ->persistent()
                                ->send();
                                
                            $this->halt();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                    
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (TeacherCircleAssignment $record): string => 
                        $record->is_active ? 'إلغاء التفعيل' : 'تفعيل'
                    )
                    ->icon(fn (TeacherCircleAssignment $record): string => 
                        $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle'
                    )
                    ->color(fn (TeacherCircleAssignment $record): string => 
                        $record->is_active ? 'danger' : 'success'
                    )
                    ->requiresConfirmation()
                    ->action(fn (TeacherCircleAssignment $record) => 
                        $record->update(['is_active' => !$record->is_active])
                    ),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                        
                    Tables\Actions\BulkAction::make('activate')
                        ->label('تفعيل المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                        
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('إلغاء تفعيل المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
