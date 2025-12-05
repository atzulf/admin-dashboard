<?php

namespace App\Filament\Widgets;

use App\Models\Story;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class StoryPerformanceChart extends ChartWidget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $heading = 'Performa Story vs Target (Tahun Ini)';

    // PENTING: sort = 2 agar muncul DI BAWAH widget angka
    protected static ?int $sort = 2;

    // Agar grafik memanjang memenuhi lebar layar
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $user = Auth::user();

        // 1. Ambil Data Real (Garis Biru)
        $dataReal = Trend::query(
            Story::query()
                ->where('status', 'published')
                ->when($user && is_callable([$user, 'hasRole']) && $user->hasRole('creative'), function ($query) use ($user) {
                    $query->where('author_id', $user->id);
                })
        )
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->count();

        // 2. Ambil Target Marketing (Garis Merah Putus-putus)
        $targetPerBulan = Cache::get('marketing_monthly_target', 10);

        // Ratakan data target agar sama panjang dengan data bulan
        $targetData = $dataReal->map(fn(TrendValue $value) => $targetPerBulan);

        return [
            'datasets' => [
                [
                    'label' => 'Realisasi Story',
                    'data' => $dataReal->map(fn(TrendValue $value) => $value->aggregate),
                    'borderColor' => '#36A2EB',
                    'backgroundColor' => '#9BD0F5',
                    'fill' => true,
                ],
                [
                    'label' => 'Target Marketing',
                    'data' => $targetData,
                    'borderColor' => '#FF6384',
                    'borderDash' => [5, 5],
                    'pointRadius' => 0,
                ],
            ],
            'labels' => $dataReal->map(fn(TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    // Tombol Edit Target (Hanya muncul untuk Marketing)
    protected function getHeaderActions(): array
    {
        return [
            Action::make('updateTarget')
                ->label('Set Target')
                ->icon('heroicon-m-adjustments-horizontal')
                ->form([
                    TextInput::make('target')
                        ->label('Target Bulanan')
                        ->numeric()
                        ->required()
                        ->default(Cache::get('marketing_monthly_target', 10)),
                ])
                ->action(function (array $data) {
                    Cache::put('marketing_monthly_target', $data['target']);

                    \Filament\Notifications\Notification::make()
                        ->title('Target Diupdate')
                        ->success()
                        ->send();

                    return redirect(request()->header('Referer'));
                })
                ->visible(fn() => auth()->user()->hasRole('marketing')),
        ];
    }
}
