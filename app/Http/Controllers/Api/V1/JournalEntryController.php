<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJournalEntryRequest;
use App\Http\Resources\JournalEntryResource;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalEntryService;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    use LogsAudit;

    /**
     * ?reference_type=sale&reference_id=5 lets a user trace "which
     * journal entry did this Sale/Purchase/Expense/etc. generate" —
     * useful now that Fase L2 auto-posts from those modules.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', JournalEntry::class);

        $query = JournalEntry::with(['lines.chartOfAccount', 'creator'])->orderByDesc('date');

        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->query('reference_type'));
        }
        if ($request->filled('reference_id')) {
            $query->where('reference_id', $request->query('reference_id'));
        }

        return JournalEntryResource::collection($query->paginate(20));
    }

    public function store(StoreJournalEntryRequest $request, JournalEntryService $service)
    {
        $this->authorize('create', JournalEntry::class);

        $entry = $service->create(
            $request->only(['date', 'description']),
            $request->validated('lines'),
            $request->user()
        );

        $this->logAudit('create', $entry, null, $entry->toArray());

        return new JournalEntryResource($entry->load(['lines.chartOfAccount', 'creator']));
    }

    public function show(JournalEntry $journal_entry)
    {
        $this->authorize('view', $journal_entry);

        return new JournalEntryResource($journal_entry->load(['lines.chartOfAccount', 'creator']));
    }

    /**
     * Soft-delete = "void" — same Section 30 principle as everywhere
     * else financial in this app. Explicitly voids the ENTRY'S LINES
     * too (not automatic — journal_entry_lines has its own independent
     * soft-delete state), so ChartOfAccount::balance() correctly stops
     * counting a voided entry's amounts.
     */
    public function destroy(JournalEntry $journal_entry)
    {
        $this->authorize('delete', $journal_entry);

        $journal_entry->lines()->delete();
        $journal_entry->delete();

        $this->logAudit('delete', $journal_entry);

        return response()->json(['message' => 'Jurnal berhasil dibatalkan (void).']);
    }
}
