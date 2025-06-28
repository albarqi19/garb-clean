<?php

namespace App\Filament\Admin\Resources\StudentResource\RelationManagers;

use App\Models\Curriculum;
use App\Models\CurriculumLevel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Components\ProgressColumn;

class CurriculaRelationManager extends RelationManager
{
    protected static string $relationship = 'curricula';
    
    protected static ?string $title = 'مناهج الطالب';
    protected static ?string $label = 'منهج';
    protected static ?string $pluralLabel = 'المناهج';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    }),
                Forms\Components\Select::make('teacher_id')
                    ->label('المعلم المشرف')
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\DatePicker::make('start_date')
                    ->label('تاريخ البدء')
                    ->required()
                    ->default(now()),
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
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('curriculum.name')
                    ->label('المنهج')
                    ->searchable(),
                Tables\Columns\TextColumn::make('curriculum.type')
                    ->label('نوع المنهج')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'منهج تلقين' => 'primary',
                        'منهج طالب' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('level.name')
                    ->label('المستوى'),
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
                    ->label('نسبة الإكمال'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البدء')
                    ->date('Y-m-d'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('curriculum_id')
                    ->relationship('curriculum', 'name')
                    ->label('المنهج'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'قيد التنفيذ' => 'قيد التنفيذ',
                        'مكتمل' => 'مكتمل',
                        'معلق' => 'معلق',
                        'ملغي' => 'ملغي',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('viewProgress')
                    ->label('عرض التقدم')
                    ->icon('heroicon-s-eye')
                    ->url(fn ($record) => route('filament.admin.resources.student-curricula.progress', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
