<?php

namespace App\Services;

/**
 * Roadmap tambahan #9 — the actual "is this still referenced?" check,
 * kept HTTP-independent (returns a label or null, never calls
 * abort()) so it can be reused by BOTH the API controllers'
 * GuardsAgainstReferencedDeletion trait (which wraps it in abort(422))
 * AND the Livewire master-data pages (whose delete buttons call
 * Eloquent directly, bypassing the API controllers entirely — so the
 * API-level guard alone would NOT have protected the web UI without
 * this shared, HTTP-agnostic check).
 */
class ReferencedDeletionChecker
{
    /**
     * @param  array<int, array{0: class-string, 1: string, 2: mixed, 3: string}>  $checks
     * @return string|null the human-readable label of the first blocking reference found, or null if none
     */
    public function firstBlockingReference(array $checks): ?string
    {
        foreach ($checks as [$modelClass, $column, $value, $label]) {
            if ($modelClass::where($column, $value)->exists()) {
                return $label;
            }
        }

        return null;
    }
}
