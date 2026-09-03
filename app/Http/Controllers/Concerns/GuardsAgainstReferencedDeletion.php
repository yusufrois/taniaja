<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ReferencedDeletionChecker;

/**
 * Roadmap tambahan #9 — "jika ada kategori yang terhubung dengan
 * transaksi lain tidak boleh dihapus agar tidak error". Applied to
 * master-data controllers (ExpenseCategory, Crop, Variety, Supplier,
 * Customer, Grade, AssetCategory) — deleting one that's still
 * referenced by existing transactions would otherwise leave those
 * transactions pointing at nothing (or, worse, silently break
 * reports/displays that expect the relation to resolve).
 *
 * Deliberately a simple existence check, not a cascading soft-delete
 * or reassignment flow — the person must clear/reassign the
 * referencing records first, which is the safer default for
 * financial/production data. The actual check lives in
 * ReferencedDeletionChecker (HTTP-agnostic) so Livewire's master-data
 * pages — whose delete buttons call Eloquent directly, bypassing
 * these API controllers entirely — can reuse the SAME logic rather
 * than a second copy that could drift out of sync.
 */
trait GuardsAgainstReferencedDeletion
{
    /**
     * @param  array<int, array{0: class-string, 1: string, 2: mixed, 3: string}>  $checks
     *                                                                                        Each entry: [ReferencingModel::class, foreignKeyColumn, valueToMatch, human-readable label for the error message]
     */
    protected function assertNotReferenced(array $checks): void
    {
        $blockingLabel = app(ReferencedDeletionChecker::class)->firstBlockingReference($checks);

        if ($blockingLabel) {
            abort(422, "Tidak bisa dihapus — masih dipakai di {$blockingLabel}. Kosongkan atau ganti dulu semua data yang memakainya sebelum menghapus.");
        }
    }
}
