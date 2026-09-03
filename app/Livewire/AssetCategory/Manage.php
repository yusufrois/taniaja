<?php

namespace App\Livewire\AssetCategory;

use App\Models\AssetCategory;
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

    public ?int $confirmingDeleteId = null;
    public ?string $deleteError = null;

    protected function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('asset_categories', 'name')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($this->editingId),
            ],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', AssetCategory::class);
    }

    public function openCreate(): void
    {
        $this->authorize('create', AssetCategory::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $category = AssetCategory::findOrFail($id);
        $this->authorize('update', $category);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $category = AssetCategory::findOrFail($this->editingId);
            $this->authorize('update', $category);
            $category->update($data);
        } else {
            $this->authorize('create', AssetCategory::class);
            AssetCategory::create($data + ['company_id' => auth()->user()->company_id]);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $category = AssetCategory::findOrFail($id);
        $this->authorize('delete', $category);
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        $category = AssetCategory::findOrFail($this->confirmingDeleteId);
        $this->authorize('delete', $category);

        // Name-based match (Asset.category is a plain string, not a
        // foreign key) — see AssetCategory model's docblock for why.
        if (\App\Models\Asset::where('category', $category->name)->exists()) {
            $this->deleteError = 'Tidak bisa dihapus — masih dipakai di data Aset Tetap.';
            return;
        }

        $category->delete();
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
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.asset-category.manage', [
            'categories' => AssetCategory::orderBy('name')->paginate(10),
            'canCreate' => auth()->user()->can('create', AssetCategory::class),
        ]);
    }
}
