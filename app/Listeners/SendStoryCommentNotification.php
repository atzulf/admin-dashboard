<?php

namespace App\Listeners;

use Kirschbaum\Commentions\Events\CommentWasCreatedEvent; // <-- Event Baru
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\StoryResource;
use App\Models\User;

class SendStoryCommentNotification
{
    public function handle(CommentWasCreatedEvent $event): void
    {
        $comment = $event->comment;

        // 1. Tentukan Siapa Penulisnya
        $penulis = null;
        if ($comment->commenter_id) {
            $penulis = User::find($comment->commenter_id);
        }
        if (! $penulis && Auth::check()) {
            $penulis = Auth::user();
        }

        // 2. Ambil Story (Object yang dikomentari)
        $story = $comment->commentable;

        // 3. Ambil Semua Orang yang Subscribe ke Story ini
        // (Biasanya Manager & Author otomatis subscribe)
        $subscribers = $story->getSubscribers();

        // 4. Looping: Kirim ke semua subscriber KECUALI penulis sendiri
        foreach ($subscribers as $subscriber) {

            // Jangan kirim notif ke diri sendiri
            if ($penulis && $subscriber->id === $penulis->id) {
                continue;
            }

            // Kirim Notifikasi
            Notification::make()
                ->title('Komentar Baru dari ' . ($penulis ? $penulis->name : 'Seseorang'))
                ->body('"' . Str::limit($comment->body, 50) . '"')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->actions([
                    Action::make('view')
                        ->label('Lihat Story')
                        ->button()
                        ->url(StoryResource::getUrl('view', ['record' => $story->id]))
                        ->markAsRead(),
                ])
                ->sendToDatabase($subscriber);
        }
    }
}
