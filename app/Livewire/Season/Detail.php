<?php

namespace App\Livewire\Season;

use App\Models\Season;
use App\Livewire\Concerns\RequiresEnabledModule;
use App\Services\SeasonReportService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Roadmap tambahan — "Detail Musim Tanam" (laporan laba rugi + rincian
 * pengeluaran per musim per greenhouse), dinaikkan prioritasnya atas
 * permintaan pengguna. Uses SeasonReportService — the EXACT same
 * numbers as the API's seasonProfitLoss/seasonHpp endpoints.
 */
#[Layout('layouts.app')]
class Detail extends Component
{
    use RequiresEnabledModule;

    public Season $season;

    public function mount(Season $season): void
    {
        $this->ensureModuleEnabled('budidaya');
        $this->authorize('view', $season);
        $this->season = $season->load(['greenhouse', 'crop', 'variety']);
    }

    public function render(SeasonReportService $seasonReport)
    {
        $user = auth()->user();

        return view('livewire.season.detail', [
            'profitLoss' => $seasonReport->profitLoss($this->season),
            'hpp' => $seasonReport->hpp($this->season),
            'expenses' => $seasonReport->expenses($this->season),
            'canViewCost' => $user->hasPermission('cost.view'),
        ]);
    }
}
