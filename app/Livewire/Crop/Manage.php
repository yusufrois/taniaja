<?php

namespace App\Livewire\Crop;

use App\Models\Crop;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Manage extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $notes = '';

    public ?int $confirmingDeleteId = null;
    public ?string $deleteError = null;

    protected function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('crops', 'name')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($this->editingId),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Crop::class);
    }

    public function openCreate(): void
    {
        $this->authorize('create', Crop::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $crop = Crop::findOrFail($id);
        $this->authorize('update', $crop);

        $this->editingId = $crop->id;
        $this->name = $crop->name;
        $this->notes = $crop->notes ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $crop = Crop::findOrFail($this->editingId);
            $this->authorize('update', $crop);
            $crop->update($data);
        } else {
            $this->authorize('create', Crop::class);
            Crop::create($data + ['company_id' => auth()->user()->company_id]);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $crop = Crop::findOrFail($id);
        $this->authorize('delete', $crop);
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        $crop = Crop::findOrFail($this->confirmingDeleteId);
        $this->authorize('delete', $crop);

        $blocking = app(\App\Services\ReferencedDeletionChecker::class)->firstBlockingReference([
            [\App\Models\Variety::class, 'crop_id', $crop->id, 'data Varietas'],
            [\App\Models\Season::class, 'crop_id', $crop->id, 'data Musim Tanam'],
        ]);
        if ($blocking) {
            $this->deleteError = "Tidak bisa dihapus — masih dipakai di {$blocking}.";
            return;
        }

        $crop->delete();
        $this->confirmingDeleteId = null;
        $this->deleteError = null;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->notes = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.crop.manage', [
            'crops' => Crop::withCount('varieties')->orderBy('name')->paginate(10),
            'canCreate' => auth()->user()->can('create', Crop::class),
        ]);
    }
}
