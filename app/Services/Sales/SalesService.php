<?php

namespace App\Services\Sales;

use App\Models\Sale;
use App\Models\StockBatch;
use App\Models\User;
use App\Services\Accounting\AccountingPostingService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates a Sale + its SaleItems in one DB transaction. Each item that
 * references a StockBatch draws down that batch's quantity_available
 * (the SAME ledger Phase 6's quick-sell uses) and snapshots cost/profit
 * at sale time — so a full multi-item invoiced Sale and Phase 6's
 * single-item quick sale both keep inventory consistent through one
 * shared source of truth, per the architecture note in Phase 6's README.
 *
 * Roadmap Fase L2 note: accounting posting for Sale is called
 * EXPLICITLY here (not via a SaleObserver on the 'created' event, the
 * way Purchase/Expense/Debt/Capital are wired) — because Sale's
 * auto-journal needs COGS, which comes from SaleItem.cost, and
 * SaleItems don't exist yet at the moment Sale itself is inserted
 * (that fires 'created' too early, before the items loop below runs).
 * postSale() is called only once everything — Sale AND its Items — is
 * fully persisted.
 */
class SalesService
{
    public function __construct(
        private InvoiceNumberGenerator $invoiceNumbers,
        private AccountingPostingService $accounting,
    ) {}

    public function create(array $header, array $items, User $user): Sale
    {
        return DB::transaction(function () use ($header, $items, $user) {
            [$subtotal, $preparedItems] = $this->prepareItems($items);

            $discount = (float) ($header['discount'] ?? 0);
            $tax = (float) ($header['tax'] ?? 0);
            $total = round($subtotal - $discount + $tax, 2);

            $sale = $this->createWithRetriedInvoiceNumber($header, $subtotal, $discount, $tax, $total, $user);

            foreach ($preparedItems as $item) {
                $sale->items()->create($item + ['company_id' => $sale->company_id]);

                if ($item['stock_batch_id']) {
                    $this->decrementBatch($item['stock_batch_id'], $item['quantity']);
                }
            }

            // Fase L2 — posted here, AFTER items exist, so the COGS
            // split (if any) reflects the real SaleItem.cost values.
            $this->accounting->postSale($sale);

            return $sale->load('items');
        });
    }

    /**
     * Validates stock availability and computes subtotal/cost/profit per
     * line BEFORE touching the database, so a mid-loop failure never
     * leaves a partially-created Sale (the whole thing is one transaction
     * regardless, but this fails fast with a clear message).
     */
    private function prepareItems(array $items): array
    {
        $subtotal = 0.0;
        $prepared = [];

        foreach ($items as $index => $item) {
            $quantity = (float) $item['quantity'];
            $price = (float) $item['price'];
            $itemSubtotal = round($quantity * $price, 2);
            $subtotal += $itemSubtotal;

            $cost = 0.0;
            $batchId = $item['stock_batch_id'] ?? null;

            if ($batchId) {
                $batch = StockBatch::find($batchId);

                if (! $batch) {
                    throw ValidationException::withMessages([
                        "items.{$index}.stock_batch_id" => 'Stock batch tidak ditemukan.',
                    ]);
                }

                if ($quantity > (float) $batch->quantity_available) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "Jumlah melebihi stok tersedia di batch ini ({$batch->quantity_available}).",
                    ]);
                }

                $cost = round($quantity * (float) $batch->unit_cost, 2);
            }

            $prepared[] = [
                'stock_batch_id' => $batchId,
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit' => $item['unit'] ?? null,
                'price' => $price,
                'subtotal' => $itemSubtotal,
                'cost' => $cost,
                'profit' => round($itemSubtotal - $cost, 2),
            ];
        }

        return [round($subtotal, 2), $prepared];
    }

    private function decrementBatch(int $batchId, float $quantity): void
    {
        $batch = StockBatch::find($batchId);
        $newAvailable = (float) $batch->quantity_available - $quantity;

        $batch->update([
            'quantity_available' => $newAvailable,
            'status' => $newAvailable <= 0 ? 'depleted' : 'active',
        ]);
    }

    /**
     * Invoice numbers are derived from a monthly count (see
     * InvoiceNumberGenerator), which has a narrow race-condition window
     * under concurrent requests. Rather than adding a locking/sequence
     * mechanism (overkill for this app's expected volume, per Aturan
     * #43), we simply retry generation a few times if the DB's unique
     * constraint on (company_id, invoice_number) rejects a collision.
     */
    private function createWithRetriedInvoiceNumber(
        array $header, float $subtotal, float $discount, float $tax, float $total, User $user
    ): Sale {
        $attempts = 0;

        while (true) {
            $attempts++;
            $invoiceNumber = $this->invoiceNumbers->generate($user->company_id, $header['company_code']);

            try {
                return Sale::create([
                    'company_id' => $user->company_id,
                    'invoice_number' => $invoiceNumber,
                    'date' => $header['date'],
                    'due_date' => $header['due_date'] ?? null,
                    'customer_id' => $header['customer_id'],
                    'greenhouse_id' => $header['greenhouse_id'] ?? null,
                    'season_id' => $header['season_id'] ?? null,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'tax' => $tax,
                    'total' => $total,
                    'notes' => $header['notes'] ?? null,
                    'created_by' => $user->id,
                ]);
            } catch (QueryException $e) {
                if ($attempts >= 3 || ! str_contains($e->getMessage(), 'invoice_number')) {
                    throw $e;
                }
                // loop and try the next sequence number
            }
        }
    }
}
