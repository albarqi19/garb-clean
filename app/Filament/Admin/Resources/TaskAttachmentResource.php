<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TaskAttachmentResource\Pages;
use App\Models\TaskAttachment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\FileUpload;

class TaskAttachmentResource extends Resource
{
    protected static ?string $model = TaskAttachment::class;

    // تعريب المورد وتغيير الأيقونة
    protected static ?string $navigationIcon = 'heroicon-o-paper-clip';
    protected static ?string $modelLabel = 'مرفق مهمة';
    protected static ?string $pluralModelLabel = 'مرفقات المهام';
    protected static ?string $navigationGroup = 'إدارة المهام والخطط';
    protected static ?int $navigationSort = 14;
    
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
                    
                Forms\Components\TextInput::make('name')
                    ->label('اسم المرفق')
                    ->required()
                    ->maxLength(255),
                    
                FileUpload::make('file_path')
                    ->label('الملف المرفق')
                    ->disk('public')
                    ->directory('task-attachments')
                    ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain', 'application/zip'])
                    ->maxSize(10240) // 10 ميجابايت
                    ->required(),
                    
                Forms\Components\TextInput::make('description')
                    ->label('وصف المرفق')
                    ->maxLength(255),
                    
                Forms\Components\Toggle::make('is_visible_to_external')
                    ->label('مرئي للمستخدمين الخارجيين')
                    ->helperText('هل يمكن للمعلمين والطلاب مشاهدة هذا المرفق؟')
                    ->default(false),
                    
                Forms\Components\Select::make('uploaded_by')
                    ->label('تم الرفع بواسطة')
                    ->relationship('uploaded_by_user', 'name')
                    ->default(fn () => Auth::id())
                    ->disabled()
                    ->dehydrated(),
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
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المرفق')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->searchable()
                    ->limit(50),
                    
                Tables\Columns\IconColumn::make('is_visible_to_external')
                    ->label('مرئي للخارج')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('file_size')
                    ->label('حجم الملف')
                    ->formatStateUsing(fn ($state) => $state ? round($state / 1024, 2) . ' KB' : '')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('file_extension')
                    ->label('نوع الملف')
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('uploaded_by_user.name')
                    ->label('تم الرفع بواسطة')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_visible_to_external')
                    ->label('المرفقات المرئية للخارج فقط')
                    ->query(fn (Builder $query) => $query->where('is_visible_to_external', true)),
                    
                Tables\Filters\Filter::make('my_attachments')
                    ->label('مرفقاتي فقط')
                    ->query(fn (Builder $query) => $query->where('uploaded_by', Auth::id())),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('تحميل')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab(),
                    
                Tables\Actions\ViewAction::make(),
                    
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->uploaded_by === Auth::id() || Auth::user()->hasRole('admin')),
                    
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->uploaded_by === Auth::id() || Auth::user()->hasRole('admin')),
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
            'index' => Pages\ListTaskAttachments::route('/'),
            'create' => Pages\CreateTaskAttachment::route('/create'),
            'edit' => Pages\EditTaskAttachment::route('/{record}/edit'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return true; // السماح لأي مستخدم بإضافة مرفق
    }
    
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return $record->uploaded_by === Auth::id() || Auth::user()->hasRole('admin');
    }
}
