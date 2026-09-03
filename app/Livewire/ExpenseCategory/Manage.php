<?php

namespace App\Livewire\ExpenseCategory;

use App\Models\ExpenseCategory;
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
                'required', 'string', 'max:100',
                \Illuminate\Validation\Rule::unique('expense_categories', 'name')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($this->editingId),
            ],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', ExpenseCategory::class);
    }

    public function openCreate(): void
    {
        $this->authorize('create', ExpenseCategory::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $category = ExpenseCategory::findOrFail($id);
        $this->authorize('update', $category);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $category = ExpenseCategory::findOrFail($this->editingId);
            $this->authorize('update', $category);
            $category->update($data);
        } else {
            $this->authorize('create', ExpenseCategory::class);
            ExpenseCategory::create($data + ['company_id' => auth()->user()->company_id]);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $category = ExpenseCategory::findOrFail($id);
        $this->authorize('delete', $category);
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        $category = ExpenseCategory::findOrFail($this->confirmingDeleteId);
        $this->authorize('delete', $category);

        $blocking = app(\App\Services\ReferencedDeletionChecker::class)->firstBlockingReference([
            [\App\Models\Expense::class, 'expense_category_id', $category->id, 'data Beban'],
        ]);
        if ($blocking) {
            $this->deleteError = "Tidak bisa dihapus — masih dipakai di {$blocking}.";
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
        return view('livewire.expense-category.manage', [
            'categories' => ExpenseCategory::orderBy('name')->paginate(10),
            'canCreate' => auth()->user()->can('create', ExpenseCategory::class),
        ]);
    }
}
