<?php

namespace App\Listeners;

use Kirschbaum\Commentions\Events\UserIsSubscribedToCommentableEvent;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

class SendStoryCommentNotification implements ShouldQueue
{
    public function handle(UserIsSubscribedToCommentableEvent $event): void
    {
        // 1. Cek: Jangan kirim notif ke diri sendiri (Penulis komentar gak perlu dikasih tau)
        if ($event->user->id === $event->comment->commenter->id) {
            return;
        }

        // 2. Kirim Notifikasi Cantik ke Lonceng Filament
        Notification::make()
            ->title('Komentar Baru')
            ->body($event->comment->commenter->name . ': "' . Str::limit($event->comment->body, 50) . '"')
            ->icon('heroicon-o-chat-bubble-left-right') // Icon Chat
            ->actions([
                // Tombol "Lihat" yang mengarah ke halaman View Story
                Action::make('view')
                    ->label('Lihat Story')
                    ->button()
                    ->url(route('filament.admin.resources.stories.view', ['record' => $event->comment->commentable_id]))
                    ->markAsRead(),
            ])
            ->sendToDatabase($event->user); // Kirim ke user yang subscribe
    }
}
