<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TaskCommentResource\Pages;
use App\Models\TaskComment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TaskCommentResource extends Resource
{
    protected static ?string $model = TaskComment::class;

    // تعريب المورد وتغيير الأيقونة
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static ?string $modelLabel = 'تعليق مهمة';
    protected static ?string $pluralModelLabel = 'تعليقات المهام';
    protected static ?string $navigationGroup = 'إدارة المهام والخطط';
    protected static ?int $navigationSort = 11;

    // إخفاء المورد من القائمة الرئيسية وجعله مرئي فقط من خلال علاقته بـالمهمة
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('task_id')
                    ->label('المهمة')
                    ->relationship('task', 'title')
                    ->required()
                    ->searchable()
                    ->preload(),
                    
                Forms\Components\Select::make('user_id')
                    ->label('المستخدم')
                    ->relationship('user', 'name')
                    ->required()
                    ->default(fn () => Auth::id())
                    ->disabled(fn () => !Auth::user()->hasRole('admin')),
                    
                Forms\Components\RichEditor::make('content')
                    ->label('محتوى التعليق')
                    ->required()
                    ->columnSpanFull(),
                    
                Forms\Components\Toggle::make('is_internal')
                    ->label('تعليق داخلي')
                    ->helperText('التعليق الداخلي يظهر فقط للإداريين والمشرفين')
                    ->default(false),
                    
                Forms\Components\Toggle::make('is_action_item')
                    ->label('إجراء مطلوب')
                    ->helperText('حدد هذا الخيار إذا كان التعليق يتضمن إجراءً يجب تنفيذه')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('task.title')
                    ->label('المهمة')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                    
                Tables\Columns\TextColumn::make('content')
                    ->label('محتوى التعليق')
                    ->html()
                    ->searchable()
                    ->limit(100)
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('كاتب التعليق')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_internal')
                    ->label('داخلي')
                    ->boolean(),
                    
                Tables\Columns\IconColumn::make('is_action_item')
                    ->label('إجراء مطلوب')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_action_item')
                    ->label('الإجراءات المطلوبة فقط')
                    ->query(fn (Builder $query) => $query->where('is_action_item', true)),
                    
                Tables\Filters\Filter::make('my_comments')
                    ->label('تعليقاتي فقط')
                    ->query(fn (Builder $query) => $query->where('user_id', Auth::id())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->user_id === Auth::id() || Auth::user()->hasRole('admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->hasRole('admin')),
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
            'index' => Pages\ListTaskComments::route('/'),
            'create' => Pages\CreateTaskComment::route('/create'),
            'edit' => Pages\EditTaskComment::route('/{record}/edit'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return true; // السماح لأي مستخدم بإضافة تعليق
    }
    
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return $record->user_id === Auth::id() || Auth::user()->hasRole('admin');
    }
}
