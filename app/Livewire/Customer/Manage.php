<?php

namespace App\Livewire\Customer;

use App\Models\Customer;
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
    public string $type = 'individual';

    public ?int $confirmingDeleteId = null;
    public ?string $deleteError = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', \Illuminate\Validation\Rule::in(['individual', 'business'])],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Customer::class);
    }

    public function openCreate(): void
    {
        $this->authorize('create', Customer::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $customer = Customer::findOrFail($id);
        $this->authorize('update', $customer);

        $this->editingId = $customer->id;
        $this->name = $customer->name;
        $this->phone = $customer->phone ?? '';
        $this->email = $customer->email ?? '';
        $this->address = $customer->address ?? '';
        $this->type = $customer->type ?? 'individual';
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $customer = Customer::findOrFail($this->editingId);
            $this->authorize('update', $customer);
            $customer->update($data);
        } else {
            $this->authorize('create', Customer::class);
            Customer::create($data + ['company_id' => auth()->user()->company_id]);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $customer = Customer::findOrFail($id);
        $this->authorize('delete', $customer);
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        $customer = Customer::findOrFail($this->confirmingDeleteId);
        $this->authorize('delete', $customer);

        $blocking = app(\App\Services\ReferencedDeletionChecker::class)->firstBlockingReference([
            [\App\Models\Sale::class, 'customer_id', $customer->id, 'data Penjualan'],
        ]);
        if ($blocking) {
            $this->deleteError = "Tidak bisa dihapus — masih dipakai di {$blocking}.";
            return;
        }

        $customer->delete();
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
        $this->type = 'individual';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.customer.manage', [
            'customers' => Customer::orderBy('name')->paginate(10),
            'canCreate' => auth()->user()->can('create', Customer::class),
        ]);
    }
}
