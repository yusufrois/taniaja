<?php

namespace App\Livewire\Asset;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\ChartOfAccount;
use App\Models\Greenhouse;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/** Standard master-data-style CRUD (full create/edit/delete), same pattern as Greenhouse. */
#[Layout('layouts.app')]
class Manage extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $greenhouse_id = null;
    public string $name = '';
    public string $category = '';
    public string $purchase_date = '';
    public ?float $value = null;
    public ?int $chart_of_account_id = null;
    public ?int $useful_life_years = null;
    public string $status = 'active';
    public string $notes = '';

    public ?int $confirmingDeleteId = null;

    protected function rules(): array
    {
        return [
            'greenhouse_id' => ['nullable', \Illuminate\Validation\Rule::exists('greenhouses', 'id')->where('company_id', auth()->user()->company_id)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'purchase_date' => ['required', 'date'],
            'value' => ['required', 'numeric', 'min:0'],
            'chart_of_account_id' => ['nullable', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', auth()->user()->company_id)->where('type', 'asset')],
            'useful_life_years' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', \Illuminate\Validation\Rule::in(['active', 'disposed', 'under_maintenance'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Asset::class);
    }

    public function openCreate(): void
    {
        $this->authorize('create', Asset::class);
        $this->resetForm();
        $this->purchase_date = now()->toDateString();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $asset = Asset::findOrFail($id);
        $this->authorize('update', $asset);

        $this->editingId = $asset->id;
        $this->greenhouse_id = $asset->greenhouse_id;
        $this->name = $asset->name;
        $this->category = $asset->category;
        $this->purchase_date = $asset->purchase_date?->toDateString() ?? '';
        $this->value = $asset->value;
        $this->chart_of_account_id = $asset->chart_of_account_id;
        $this->useful_life_years = $asset->useful_life_years;
        $this->status = $asset->status;
        $this->notes = $asset->notes ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $asset = Asset::findOrFail($this->editingId);
            $this->authorize('update', $asset);
            $asset->update($data);
        } else {
            $this->authorize('create', Asset::class);
            Asset::create($data + ['company_id' => auth()->user()->company_id]);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $asset = Asset::findOrFail($id);
        $this->authorize('delete', $asset);
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        $asset = Asset::findOrFail($this->confirmingDeleteId);
        $this->authorize('delete', $asset);
        $asset->delete();
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
        $this->name = '';
        $this->category = '';
        $this->purchase_date = '';
        $this->value = null;
        $this->chart_of_account_id = null;
        $this->useful_life_years = null;
        $this->status = 'active';
        $this->notes = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.asset.manage', [
            'assets' => Asset::with('greenhouse')->orderByDesc('purchase_date')->paginate(10),
            'greenhouses' => Greenhouse::orderBy('code')->get(),
            'accounts' => ChartOfAccount::where('is_active', true)->where('type', 'asset')->orderBy('code')->get(),
            'categories' => AssetCategory::orderBy('name')->get(),
            'canCreate' => $user->can('create', Asset::class),
            'canViewCost' => $user->hasPermission('cost.view'),
        ]);
    }
}
