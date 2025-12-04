<?php

namespace App\Filament\Resources\StoryResource\Pages;

use App\Filament\Resources\StoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListStories extends ListRecords
{
    protected static string $resource = StoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            'waiting for review' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'waiting for review')),
            'needs revision' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'needs revision')),
            'approved' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'approved')),
            'published' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'published')),
        ];
    }
}
