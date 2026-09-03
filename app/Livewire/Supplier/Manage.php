<?php

namespace App\Livewire\Supplier;

use App\Models\Supplier;
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
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public string $notes = '';

    public ?int $confirmingDeleteId = null;
    public ?string $deleteError = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Supplier::class);
    }

    public function openCreate(): void
    {
        $this->authorize('create', Supplier::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $supplier = Supplier::findOrFail($id);
        $this->authorize('update', $supplier);

        $this->editingId = $supplier->id;
        $this->name = $supplier->name;
        $this->phone = $supplier->phone ?? '';
        $this->email = $supplier->email ?? '';
        $this->address = $supplier->address ?? '';
        $this->notes = $supplier->notes ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $supplier = Supplier::findOrFail($this->editingId);
            $this->authorize('update', $supplier);
            $supplier->update($data);
        } else {
            $this->authorize('create', Supplier::class);
            Supplier::create($data + ['company_id' => auth()->user()->company_id]);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $supplier = Supplier::findOrFail($id);
        $this->authorize('delete', $supplier);
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        $supplier = Supplier::findOrFail($this->confirmingDeleteId);
        $this->authorize('delete', $supplier);

        $blocking = app(\App\Services\ReferencedDeletionChecker::class)->firstBlockingReference([
            [\App\Models\Purchase::class, 'supplier_id', $supplier->id, 'data Pembelian'],
            [\App\Models\InputPurchase::class, 'supplier_id', $supplier->id, 'data Pembelian Pupuk'],
        ]);
        if ($blocking) {
            $this->deleteError = "Tidak bisa dihapus — masih dipakai di {$blocking}.";
            return;
        }

        $supplier->delete();
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
        $this->phone = '';
        $this->email = '';
        $this->address = '';
        $this->notes = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.supplier.manage', [
            'suppliers' => Supplier::orderBy('name')->paginate(10),
            'canCreate' => auth()->user()->can('create', Supplier::class),
        ]);
    }
}
