<?php

namespace App\Filament\Resources\StoryResource\Pages;

use App\Filament\Resources\StoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewStory extends ViewRecord
{
    protected static string $resource = StoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('published')
                ->label('Publish Story')
                ->visible(fn() => auth()->user()->hasRole('manager') && $this->record->status === 'approved')
                ->form([
                    \Filament\Forms\Components\Textarea::make('feedback')
                        ->label('Editor Feedback')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    $this->record->status = 'published';
                    $this->record->feedback = $data['feedback'];
                    $this->record->save();
                    Notification::make()
                        ->title('Story Published')
                        ->body('The story has been successfully published.')
                        ->success()
                        ->send();
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                })
                ->requiresConfirmation()
                ->modalHeading('Publish Story')
                ->modalDescription('Are you sure you want to publish this story?'),

            Actions\Action::make('Cancel')
                ->label('Cancel Story')
                ->color('danger')
                ->visible(fn() => \Illuminate\Support\Facades\Auth::check() && (
                    \Illuminate\Support\Facades\Auth::user()->hasRole('admin')
                    || $this->record->author_id === \Illuminate\Support\Facades\Auth::id()
                ) && in_array($this->record->status, ['waiting for review', 'needs revision', 'approved', 'published']))
                ->requiresConfirmation()
                ->modalHeading('Cancel Story')
                ->modalDescription('Are you sure you want to cancel this story? This action cannot be undone.')
                ->action(function () {
                    $this->record->status = 'cancelled';
                    $this->record->save();
                    $this->notify('success', 'Story cancelled successfully.');
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('Revision')
                ->label('Need Revision')
                ->visible(fn() => auth()->user()->hasRole(['manager', 'admin']) && in_array($this->record->status, ['waiting for review', 'needs revision', 'approved', 'published']))
                ->form([
                    \Filament\Forms\Components\Textarea::make('feedback')
                        ->label('Revision Feedback')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    $this->record->status = 'needs revision';
                    $this->record->feedback = $data['feedback'];
                    $this->record->save();
                    Notification::make()
                        ->title('Revision Requested')
                        ->body('The author has been notified to make revisions.')
                        ->success()
                        ->send();
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                })
                ->requiresConfirmation()
                ->modalHeading('Request Revision')
                ->modalDescription('Are you sure you want to request a revision for this story?'),

        ];
    }
}
