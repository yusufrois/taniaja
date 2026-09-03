<?php

namespace App\Livewire\Season;

use App\Livewire\Concerns\RequiresEnabledModule;
use App\Models\Crop;
use App\Models\Greenhouse;
use App\Models\Season;
use App\Services\SeasonStatusService;
use App\Models\Variety;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Season is more complex than the plain master-data pages (Fase 9's
 * earlier batch): crop_id/variety_id are a CASCADING pair (variety
 * options depend on the chosen crop — updatedCropId() resets it when
 * crop changes), and there's a cross-field business rule (a
 * greenhouse can't have two concurrently-running seasons) already
 * enforced by Store/UpdateSeasonRequest on the API side — replicated
 * here via the SAME withValidator-equivalent check so the UI gives
 * the identical rejection, not a generic DB error.
 */
#[Layout('layouts.app')]
class Manage extends Component
{
    use WithPagination, RequiresEnabledModule;

    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $greenhouse_id = null;
    public ?int $crop_id = null;
    public ?int $variety_id = null;
    public string $season_name = '';
    public string $planting_date = '';
    public string $estimated_harvest_date = '';
    public string $actual_harvest_date = '';
    public ?int $plant_count = null;
    public ?float $target_yield = null;
    public string $status = 'planning';
    public string $notes = '';

    public ?int $confirmingDeleteId = null;

    protected function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'greenhouse_id' => ['required', \Illuminate\Validation\Rule::exists('greenhouses', 'id')->where('company_id', $companyId)],
            'crop_id' => ['required', \Illuminate\Validation\Rule::exists('crops', 'id')->where('company_id', $companyId)],
            'variety_id' => ['required', \Illuminate\Validation\Rule::exists('varieties', 'id')->where('company_id', $companyId)],
            'season_name' => ['required', 'string', 'max:255'],
            'planting_date' => ['required', 'date'],
            'estimated_harvest_date' => ['nullable', 'date', 'after_or_equal:planting_date'],
            'actual_harvest_date' => ['nullable', 'date', 'after_or_equal:planting_date'],
            'plant_count' => ['nullable', 'integer', 'min:0'],
            'target_yield' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', \Illuminate\Validation\Rule::in(['planning', 'active', 'harvesting', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'estimated_harvest_date.after_or_equal' => 'Estimasi panen tidak boleh sebelum tanggal tanam.',
            'actual_harvest_date.after_or_equal' => 'Tanggal panen aktual tidak boleh sebelum tanggal tanam.',
        ];
    }

    public function mount(): void
    {
        $this->ensureModuleEnabled('budidaya');
        $this->authorize('viewAny', Season::class);
    }

    /** Crop changed in the dropdown — its old variety choice may no longer be valid, so clear it. */
    public function updatedCropId(): void
    {
        $this->variety_id = null;
    }

    public function openCreate(): void
    {
        $this->authorize('create', Season::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $season = Season::findOrFail($id);
        $this->authorize('update', $season);

        $this->editingId = $season->id;
        $this->greenhouse_id = $season->greenhouse_id;
        $this->crop_id = $season->crop_id;
        $this->variety_id = $season->variety_id;
        $this->season_name = $season->season_name;
        $this->planting_date = $season->planting_date?->toDateString() ?? '';
        $this->estimated_harvest_date = $season->estimated_harvest_date?->toDateString() ?? '';
        $this->actual_harvest_date = $season->actual_harvest_date?->toDateString() ?? '';
        $this->plant_count = $season->plant_count;
        $this->target_yield = $season->target_yield;
        $this->status = $season->status;
        $this->notes = $season->notes ?? '';
        $this->showModal = true;
    }

    /**
     * Same "one running season per greenhouse" rule enforced by the
     * API (Store/UpdateSeasonRequest) — replicated here so the UI
     * rejects it as a normal validation error, not a raw exception.
     */
    private function assertNoOtherRunningSeasonInGreenhouse(): void
    {
        if (! in_array($this->status, ['planning', 'active', 'harvesting'], true)) {
            return;
        }

        $hasOther = Season::where('greenhouse_id', $this->greenhouse_id)
            ->whereIn('status', ['planning', 'active', 'harvesting'])
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->exists();

        if ($hasOther) {
            $this->addError('greenhouse_id', 'Greenhouse ini sudah memiliki musim tanam lain yang masih berjalan.');
        }
    }

    private function assertVarietyBelongsToCrop(): void
    {
        $ok = Variety::where('id', $this->variety_id)->where('crop_id', $this->crop_id)->exists();
        if (! $ok) {
            $this->addError('variety_id', 'Varietas yang dipilih tidak termasuk dalam crop yang dipilih.');
        }
    }

    public function save(): void
    {
        $data = $this->validate();

        $this->resetErrorBag();
        $this->assertVarietyBelongsToCrop();
        $this->assertNoOtherRunningSeasonInGreenhouse();
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $data['estimated_harvest_date'] = $data['estimated_harvest_date'] ?: null;
        $data['actual_harvest_date'] = $data['actual_harvest_date'] ?: null;

        // Roadmap Phase 10 — same logic the API now uses too, via
        // SeasonStatusService (single source of truth for web + API/
        // Flutter). See that class's docblock for the bug #8 story.
        $data = app(SeasonStatusService::class)->applyHarvestTrigger($data);

        if ($this->editingId) {
            $season = Season::findOrFail($this->editingId);
            $this->authorize('update', $season);
            $season->update($data);
        } else {
            $this->authorize('create', Season::class);
            Season::create($data + ['company_id' => auth()->user()->company_id]);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $season = Season::findOrFail($id);
        $this->authorize('delete', $season);
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        $season = Season::findOrFail($this->confirmingDeleteId);
        $this->authorize('delete', $season);
        $season->delete();
        $this->confirmingDeleteId = null;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->greenhouse_id = null;
        $this->crop_id = null;
        $this->variety_id = null;
        $this->season_name = '';
        $this->planting_date = '';
        $this->estimated_harvest_date = '';
        $this->actual_harvest_date = '';
        $this->plant_count = null;
        $this->target_yield = null;
        $this->status = 'planning';
        $this->notes = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        // Roadmap Phase 10 — same lazy promotion the API's index()
        // now does too, via SeasonStatusService.
        app(SeasonStatusService::class)->promoteDueSeasons(auth()->user()->company_id);

        return view('livewire.season.manage', [
            'seasons' => Season::with(['greenhouse', 'crop', 'variety'])->orderByDesc('planting_date')->paginate(10),
            'greenhouses' => Greenhouse::orderBy('code')->get(),
            'crops' => Crop::orderBy('name')->get(),
            'varieties' => $this->crop_id ? Variety::where('crop_id', $this->crop_id)->orderBy('name')->get() : collect(),
            'canCreate' => auth()->user()->can('create', Season::class),
        ]);
    }
}
