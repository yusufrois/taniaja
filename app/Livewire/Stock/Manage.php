<?php

namespace App\Livewire\Stock;

use App\Models\StockBatch;
use App\Models\StockBatchSale;
use App\Services\Stock\StockBatchService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Roadmap Fase UI-1 — halaman "Stok/Gudang", item #3. Menampilkan
 * stok dari KEDUA sumber (panen sendiri & hasil beli) berdampingan
 * tapi tetap bisa dibedakan (source_type), sesuai desain
 * StockBatchController::index() yang sudah ada — plus aksi "Jual
 * Cepat" langsung dari sini (StockBatchController::sell()).
 */
#[Layout('layouts.app')]
class Manage extends Component
{
    use WithPagination;

    public string $sourceFilter = '';

    public ?int $sellingBatchId = null;
    public ?int $customer_id = null;
    public string $quantity_sold = '';
    public string $sale_price_per_unit = '';
    public string $notes = '';

    protected function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'customer_id' => ['nullable', \Illuminate\Validation\Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'quantity_sold' => ['required', 'numeric', 'min:0.01'],
            'sale_price_per_unit' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', StockBatchSale::class);
    }

    public function updatingSourceFilter(): void
    {
        $this->resetPage();
    }

    public function openSell(int $batchId): void
    {
        $this->authorize('create', StockBatchSale::class);
        $this->sellingBatchId = $batchId;
        $this->customer_id = null;
        $this->quantity_sold = '';
        $this->sale_price_per_unit = '';
        $this->notes = '';
        $this->resetErrorBag();
    }

    public function closeSell(): void
    {
        $this->sellingBatchId = null;
        $this->resetErrorBag();
    }

    public function sell(StockBatchService $stockBatchService): void
    {
        $this->authorize('create', StockBatchSale::class);
        $data = $this->validate();

        $batch = StockBatch::findOrFail($this->sellingBatchId);

        // StockBatchService::sell() throws ValidationException itself
        // (e.g. selling more than quantity_available) — Livewire's
        // $this->validate() call above already established the error
        // bag pattern, so a thrown ValidationException here surfaces
        // the same way automatically.
        $stockBatchService->sell(
            $batch,
            (float) $data['quantity_sold'],
            (float) $data['sale_price_per_unit'],
            $data['customer_id'],
            $data['notes'] ?: null,
            auth()->id(),
        );

        $this->sellingBatchId = null;
    }

    public function render()
    {
        $companyId = auth()->user()->company_id;
        $user = auth()->user();

        $query = StockBatch::with(['crop', 'variety', 'grade'])->where('status', 'active');

        if ($this->sourceFilter) {
            $query->where('source_type', $this->sourceFilter);
        }

        return view('livewire.stock.manage', [
            'batches' => $query->orderByDesc('acquired_date')->paginate(15),
            'customers' => \App\Models\Customer::where('company_id', $companyId)->orderBy('name')->get(),
            'canSell' => $user->can('create', StockBatchSale::class),
            'canViewCost' => $user->hasPermission('cost.view'),
            'sellingBatch' => $this->sellingBatchId ? StockBatch::with(['crop', 'variety', 'grade'])->find($this->sellingBatchId) : null,
        ]);
    }
}
