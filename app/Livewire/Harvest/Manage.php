<?php

namespace App\Livewire\Harvest;

use App\Models\Grade;
use App\Models\Harvest;
use App\Models\Season;
use App\Services\Stock\StockBatchService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Roadmap Fase UI-1 — halaman "Panen", item #2. Mengikuti PERSIS
 * kontrak HarvestController API: 1 Panen + N HarvestItem (per grade)
 * + StockBatchService::createFromHarvestItem() per item, semuanya
 * dalam 1 transaksi — supaya panen tidak pernah ada tanpa jadi stok
 * yang bisa dijual, dan sebaliknya.
 */
#[Layout('layouts.app')]
class Manage extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public ?int $confirmingDeleteId = null;

    public ?int $season_id = null;
    public string $harvest_date = '';
    public string $notes = '';

    /** @var array<int, array{grade_id: ?int, weight: ?float, quantity: ?float, quality_notes: string}> */
    public array $items = [];

    protected function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'season_id' => ['required', \Illuminate\Validation\Rule::exists('seasons', 'id')->where('company_id', $companyId)],
            'harvest_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.grade_id' => ['required', \Illuminate\Validation\Rule::exists('grades', 'id')->where('company_id', $companyId)],
            'items.*.weight' => ['required', 'numeric', 'min:0.01'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.quality_notes' => ['nullable', 'string'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Harvest::class);
    }

    public function openCreate(): void
    {
        $this->authorize('create', Harvest::class);
        $this->resetForm();
        $this->addItem();
        $this->showModal = true;
    }

    public function addItem(): void
    {
        $this->items[] = ['grade_id' => null, 'weight' => null, 'quantity' => null, 'quality_notes' => ''];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function save(StockBatchService $stockBatchService, \App\Services\SeasonStatusService $seasonStatusService): void
    {
        $this->authorize('create', Harvest::class);
        $data = $this->validate();

        $season = Season::findOrFail($data['season_id']);

        DB::transaction(function () use ($data, $season, $stockBatchService, $seasonStatusService) {
            $harvest = Harvest::create([
                'company_id' => auth()->user()->company_id,
                'season_id' => $season->id,
                'greenhouse_id' => $season->greenhouse_id,
                'variety_id' => $season->variety_id,
                'harvest_date' => $data['harvest_date'],
                'notes' => $data['notes'] ?: null,
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $itemData) {
                $item = $harvest->items()->create([
                    'company_id' => $harvest->company_id,
                    'grade_id' => $itemData['grade_id'],
                    'quantity' => $itemData['quantity'] ?: null,
                    'weight' => $itemData['weight'],
                    'quality_notes' => $itemData['quality_notes'] ?: null,
                ]);

                $stockBatchService->createFromHarvestItem($item);
            }

            // Roadmap tambahan — "status Musim Tanam harus ikut
            // berubah jadi Panen begitu Panen dicatat, tapi TIDAK
            // PERNAH otomatis jadi Selesai". Reuses
            // SeasonStatusService (the SAME rule the Season edit
            // form's "Tanggal Panen Aktual" field already triggers)
            // rather than duplicating the completed/cancelled guard
            // logic here — single source of truth, same principle as
            // the rest of Phase 10's API-parity work. Only sets
            // actual_harvest_date if this is the FIRST harvest
            // recorded for the season — later rounds (cabe ronde 2,
            // 3, dst) don't push that date forward again.
            //
            // Trigger check deliberately uses THIS harvest's own
            // $data['harvest_date'] — not $season->actual_harvest_date
            // — because that field might already hold a stale or
            // future-dated value from an earlier, unrelated edit (the
            // Season form's own "Tanggal Panen Aktual" field), which
            // would otherwise block the status flip even though a
            // real harvest is being recorded right now.
            $seasonUpdate = $seasonStatusService->applyHarvestTrigger([
                'status' => $season->status,
                'actual_harvest_date' => $data['harvest_date'],
            ]);

            $season->update([
                'actual_harvest_date' => $season->actual_harvest_date ?? $data['harvest_date'],
                'status' => $seasonUpdate['status'],
            ]);
        });

        $this->showModal = false;
        $this->resetForm();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $harvest = Harvest::findOrFail($id);
        $this->authorize('delete', $harvest);
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        $harvest = Harvest::findOrFail($this->confirmingDeleteId);
        $this->authorize('delete', $harvest);
        $harvest->delete();
        $this->confirmingDeleteId = null;
    }

    private function resetForm(): void
    {
        $this->season_id = null;
        $this->harvest_date = now()->toDateString();
        $this->notes = '';
        $this->items = [];
        $this->resetErrorBag();
    }

    public function render()
    {
        $companyId = auth()->user()->company_id;

        return view('livewire.harvest.manage', [
            'harvests' => Harvest::with(['season', 'greenhouse', 'items.grade'])
                ->where('company_id', $companyId)->orderByDesc('harvest_date')->paginate(10),
            'seasons' => Season::with('greenhouse')->where('company_id', $companyId)
                ->whereIn('status', ['active', 'harvesting'])->orderByDesc('planting_date')->get(),
            'grades' => Grade::where('company_id', $companyId)->orderBy('name')->get(),
            'canCreate' => auth()->user()->can('create', Harvest::class),
        ]);
    }
}
