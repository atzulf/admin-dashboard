<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoryResource\Pages;
use App\Models\Story;
use App\Models\User; // Import User untuk mention
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

// IMPORT PENTING UNTUK TAMPILAN VIEW & KOMENTAR
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Kirschbaum\Commentions\Filament\Infolists\Components\CommentsEntry;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Awcodes\Curator\Components\Tables\CuratorColumn;

class StoryResource extends Resource
{
    protected static ?string $model = Story::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    // 1. FORM (Halaman Edit & Create) - Tidak ada komentar di sini
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(150)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('content')
                    ->required()
                    ->rows(14)
                    ->columnSpanFull(),

                CuratorPicker::make('featured_image_id')
                    ->label('Featured Image')
                    ->relationship('featuredImage', 'id') // Sesuai nama fungsi di Model
                    ->buttonLabel('Pilih dari Library')

                    ->columnSpanFull(),
            ]);
    }

    // 2. INFOLIST (Halaman View) - Komentar muncul di sini!
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Bagian Atas: Detail Story
                Section::make('Detail Story')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Judul')
                            ->weight('bold')
                            ->size(TextEntry\TextEntrySize::Large),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'published' => 'primary',
                                'approved' => 'success',
                                'needs revision' => 'danger',
                                default => 'warning',
                            }),

                        TextEntry::make('author.name')
                            ->label('Penulis')
                            ->icon('heroicon-m-user'),

                        TextEntry::make('created_at')
                            ->label('Dibuat Tanggal')
                            ->dateTime(),

                        TextEntry::make('content')
                            ->label('Isi Story')
                            ->markdown()
                            ->columnSpanFull(),
                    ])->columns(2),

                // Bagian Bawah: DISKUSI REVISI (Plugin Kirschbaum)
                Section::make('Diskusi & Revisi')
                    ->description('Area diskusi antara Manager dan Creative.')
                    ->schema([
                        CommentsEntry::make('comments')
                            ->mentionables(fn($record) => User::all()) // Fitur mention user
                    ]),
            ]);
    }

    // 3. TABLE (Halaman List)
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'waiting for review',
                        'danger' => 'needs revision',
                        'success' => 'approved',
                        'primary' => 'published',
                        'secondary' => 'cancelled',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('author.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                CuratorColumn::make('featuredImage')
                    ->label('Cover')
                    ->size(50),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(), // Tombol ini akan membuka Infolist di atas
                Tables\Actions\EditAction::make(),

                // Action Delete khusus Admin
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(): bool => auth()->user()->hasRole('admin'))
                    ->requiresConfirmation(true)
                    ->modalHeading('Delete Story')
                    ->successNotificationTitle('Story Deleted'),

                // Action Review khusus Manager
                Tables\Actions\Action::make('Review')
                    ->label('Approve') // Saya ganti label jadi Approve biar jelas
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Story $record) => auth()->user()->hasRole('manager') && in_array($record->status, ['waiting for review', 'needs revision']))
                    ->requiresConfirmation()
                    ->modalHeading('Approve Story')
                    ->action(function (Story $record) {
                        $record->update(['status' => 'approved']);
                        // Redirect ke halaman View agar bisa lanjut komen
                        return redirect(static::getUrl('view', ['record' => $record]));
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ])->visible(fn() => auth()->user()->hasRole('admin')),
            ]);
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
            'index' => Pages\ListStories::route('/'),
            'create' => Pages\CreateStory::route('/create'),
            'view' => Pages\ViewStory::route('/{record}'),
            'edit' => Pages\EditStory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->when(auth()->user()->hasRole('creative'), function (Builder $query) {
                $query->where('author_id', auth()->id());
            });
    }
}
