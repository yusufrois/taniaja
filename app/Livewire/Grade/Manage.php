<?php

namespace App\Livewire\Grade;

use App\Models\Grade;
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
    public ?int $sort_order = null;

    public ?int $confirmingDeleteId = null;
    public ?string $deleteError = null;

    protected function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100',
                \Illuminate\Validation\Rule::unique('grades', 'name')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($this->editingId),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Grade::class);
    }

    public function openCreate(): void
    {
        $this->authorize('create', Grade::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $grade = Grade::findOrFail($id);
        $this->authorize('update', $grade);

        $this->editingId = $grade->id;
        $this->name = $grade->name;
        $this->sort_order = $grade->sort_order;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $grade = Grade::findOrFail($this->editingId);
            $this->authorize('update', $grade);
            $grade->update($data);
        } else {
            $this->authorize('create', Grade::class);
            Grade::create($data + ['company_id' => auth()->user()->company_id]);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $grade = Grade::findOrFail($id);
        $this->authorize('delete', $grade);
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        $grade = Grade::findOrFail($this->confirmingDeleteId);
        $this->authorize('delete', $grade);

        $blocking = app(\App\Services\ReferencedDeletionChecker::class)->firstBlockingReference([
            [\App\Models\HarvestItem::class, 'grade_id', $grade->id, 'data Hasil Panen'],
        ]);
        if ($blocking) {
            $this->deleteError = "Tidak bisa dihapus — masih dipakai di {$blocking}.";
            return;
        }

        $grade->delete();
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
        $this->sort_order = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.grade.manage', [
            'grades' => Grade::orderBy('sort_order')->orderBy('name')->paginate(10),
            'canCreate' => auth()->user()->can('create', Grade::class),
        ]);
    }
}
