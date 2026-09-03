<?php

namespace App\Livewire\Variety;

use App\Models\Crop;
use App\Models\Variety;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Manage extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $crop_id = null;
    public string $name = '';
    public string $notes = '';

    /** ?crop_id= from the URL (linked from Crop's "Varietas" button) filters the list. */
    public ?int $filterCropId = null;

    public ?int $confirmingDeleteId = null;
    public ?string $deleteError = null;

    protected function rules(): array
    {
        return [
            'crop_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('crops', 'id')->where('company_id', auth()->user()->company_id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Variety::class);
        $this->filterCropId = request()->integer('crop_id') ?: null;
    }

    public function openCreate(): void
    {
        $this->authorize('create', Variety::class);
        $this->resetForm();
        $this->crop_id = $this->filterCropId;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $variety = Variety::findOrFail($id);
        $this->authorize('update', $variety);

        $this->editingId = $variety->id;
        $this->crop_id = $variety->crop_id;
        $this->name = $variety->name;
        $this->notes = $variety->notes ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $variety = Variety::findOrFail($this->editingId);
            $this->authorize('update', $variety);
            $variety->update($data);
        } else {
            $this->authorize('create', Variety::class);
            Variety::create($data + ['company_id' => auth()->user()->company_id]);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $variety = Variety::findOrFail($id);
        $this->authorize('delete', $variety);
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        $variety = Variety::findOrFail($this->confirmingDeleteId);
        $this->authorize('delete', $variety);

        $blocking = app(\App\Services\ReferencedDeletionChecker::class)->firstBlockingReference([
            [\App\Models\Season::class, 'variety_id', $variety->id, 'data Musim Tanam'],
        ]);
        if ($blocking) {
            $this->deleteError = "Tidak bisa dihapus — masih dipakai di {$blocking}.";
            return;
        }

        $variety->delete();
        $this->confirmingDeleteId = null;
        $this->deleteError = null;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function clearFilter(): void
    {
        $this->filterCropId = null;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->crop_id = null;
        $this->name = '';
        $this->notes = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Variety::with('crop')->orderBy('name');

        if ($this->filterCropId) {
            $query->where('crop_id', $this->filterCropId);
        }

        return view('livewire.variety.manage', [
            'varieties' => $query->paginate(10),
            'crops' => Crop::orderBy('name')->get(),
            'filterCrop' => $this->filterCropId ? Crop::find($this->filterCropId) : null,
            'canCreate' => auth()->user()->can('create', Variety::class),
        ]);
    }
}
