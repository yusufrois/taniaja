<?php

namespace App\Services;

use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Same retry-on-collision reasoning as SalesService's invoice number
 * generation (Phase 7) — narrow race-condition window under
 * concurrent requests, not worth a locking/sequence mechanism for
 * this app's expected volume (Aturan #43).
 */
class DeliveryNoteService
{
    public function __construct(private DeliveryNoteNumberGenerator $numbers) {}

    public function create(array $header, array $items, User $user): DeliveryNote
    {
        $attempts = 0;

        while (true) {
            $attempts++;
            $number = $this->numbers->generate($user->company_id, $user->company->code);

            try {
                return DB::transaction(function () use ($header, $items, $number, $user) {
                    $note = DeliveryNote::create([
                        'company_id' => $user->company_id,
                        'delivery_number' => $number,
                        'customer_id' => $header['customer_id'],
                        'sale_id' => $header['sale_id'] ?? null,
                        'date' => $header['date'],
                        'notes' => $header['notes'] ?? null,
                        'created_by' => $user->id,
                    ]);

                    foreach ($items as $item) {
                        DeliveryNoteItem::create([
                            'company_id' => $user->company_id,
                            'delivery_note_id' => $note->id,
                            'description' => $item['description'],
                            'quantity' => $item['quantity'],
                            'unit' => $item['unit'],
                        ]);
                    }

                    return $note;
                });
            } catch (QueryException $e) {
                if ($attempts >= 3 || ! str_contains($e->getMessage(), 'delivery_number')) {
                    throw $e;
                }
                // loop and try the next sequence number
            }
        }
    }
}
