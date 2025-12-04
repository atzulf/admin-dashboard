<?php

namespace App\Filament\Widgets;

use App\Models\Story;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StoryStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        /** @var \App\Models\User|null $user */

        return [
            Stat::make('Dafa Kopling Matic', '100%'),

            Stat::make('Waiting for Review', Story::where('status', 'waiting for review')->when($user?->hasRole('creative'), function ($query) use ($user) {
                $query->where('author_id', $user?->id);
            })
                ->count())->icon('heroicon-o-clock'),

            Stat::make('Needs Revision', Story::where('status', 'needs revision')->when($user?->hasRole('creative'), function ($query) use ($user) {
                $query->where('author_id', $user?->id);
            })
                ->count())->icon('heroicon-o-pencil'),

            Stat::make('Approved', Story::where('status', 'approved')->when($user?->hasRole('creative'), function ($query) use ($user) {
                $query->where('author_id', $user?->id);
            })
                ->count())->icon('heroicon-o-check-circle'),

            Stat::make('Published', Story::where('status', 'published')->when($user?->hasRole('creative'), function ($query) use ($user) {
                $query->where('author_id', $user?->id);
            })
                ->count())->icon('heroicon-o-book-open'),

        ];
    }
}
