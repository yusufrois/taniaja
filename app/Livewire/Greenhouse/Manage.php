<?php

namespace App\Livewire\Greenhouse;

use App\Livewire\Concerns\RequiresEnabledModule;
use App\Models\Greenhouse;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Master-data CRUD reference pattern for Phase 9 — list + create/edit
 * modal + delete, all in ONE component (common Livewire convention for
 * simple CRUD, avoids a separate route/component per action). Every
 * other master-data page (Crop, Variety, Supplier, Customer, Grade,
 * ExpenseCategory) should follow this SAME structure so they stay
 * consistent and easy to maintain as a set.
 *
 * Authorization mirrors the API exactly — GreenhousePolicy (already
 * established, module 'greenhouse') is reused as-is via $this->authorize(),
 * not re-implemented here. A permission a role doesn't have via the API
 * is the same permission they don't have here.
 *
 * 'capacity' is deliberately NOT a form field — StoreGreenhouseRequest
 * validates it but Greenhouse's $fillable never included it, so it was
 * never actually persisted via the API either (pre-existing, unrelated
 * to this UI work — noted, not fixed here to avoid scope creep).
 */
#[Layout('layouts.app')]
class Manage extends Component
{
    use WithPagination, RequiresEnabledModule;

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $code = '';
    public string $name = '';
    public string $location = '';
    public ?float $length = null;
    public ?float $width = null;
    public ?float $area = null;
    public string $status = 'active';
    public string $notes = '';

    public ?int $confirmingDeleteId = null;

    protected function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'code' => [
                'required', 'string', 'max:50',
                \Illuminate\Validation\Rule::unique('greenhouses', 'code')
                    ->where('company_id', $companyId)
                    ->ignore($this->editingId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            // Roadmap tambahan #5 — `area` is no longer directly
            // editable; Greenhouse::booted() always (re)computes it
            // from length × width on save, so it's deliberately left
            // out of validate()'s data here.
            'status' => ['required', \Illuminate\Validation\Rule::in(['active', 'inactive', 'under_construction'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function mount(): void
    {
        $this->ensureModuleEnabled('budidaya');
        $this->authorize('viewAny', Greenhouse::class);
    }

    /** Roadmap tambahan #5 — live preview only; the persisted value always comes from Greenhouse::booted(). */
    public function updatedLength(): void
    {
        $this->recomputeAreaPreview();
    }

    public function updatedWidth(): void
    {
        $this->recomputeAreaPreview();
    }

    private function recomputeAreaPreview(): void
    {
        $this->area = ($this->length !== null && $this->width !== null)
            ? round($this->length * $this->width, 2)
            : null;
    }

    public function openCreate(): void
    {
        $this->authorize('create', Greenhouse::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $greenhouse = Greenhouse::findOrFail($id);
        $this->authorize('update', $greenhouse);

        $this->editingId = $greenhouse->id;
        $this->code = $greenhouse->code;
        $this->name = $greenhouse->name;
        $this->location = $greenhouse->location ?? '';
        $this->length = $greenhouse->length;
        $this->width = $greenhouse->width;
        $this->area = $greenhouse->area;
        $this->status = $greenhouse->status;
        $this->notes = $greenhouse->notes ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $greenhouse = Greenhouse::findOrFail($this->editingId);
            $this->authorize('update', $greenhouse);
            $greenhouse->update($data);
        } else {
            $this->authorize('create', Greenhouse::class);
            Greenhouse::create($data + ['company_id' => auth()->user()->company_id]);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $greenhouse = Greenhouse::findOrFail($id);
        $this->authorize('delete', $greenhouse);
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        $greenhouse = Greenhouse::findOrFail($this->confirmingDeleteId);
        $this->authorize('delete', $greenhouse);
        $greenhouse->delete();
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
        $this->code = '';
        $this->name = '';
        $this->location = '';
        $this->length = null;
        $this->width = null;
        $this->area = null;
        $this->status = 'active';
        $this->notes = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.greenhouse.manage', [
            'greenhouses' => Greenhouse::orderBy('code')->paginate(10),
            'canCreate' => auth()->user()->can('create', Greenhouse::class),
        ]);
    }
}
