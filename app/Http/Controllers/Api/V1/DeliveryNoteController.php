<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeliveryNoteRequest;
use App\Http\Resources\DeliveryNoteResource;
use App\Models\DeliveryNote;
use App\Services\DeliveryNoteService;

class DeliveryNoteController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', DeliveryNote::class);

        return DeliveryNoteResource::collection(
            DeliveryNote::with(['customer', 'sale'])->orderByDesc('date')->paginate(20)
        );
    }

    public function store(StoreDeliveryNoteRequest $request, DeliveryNoteService $service)
    {
        $this->authorize('create', DeliveryNote::class);

        $note = $service->create(
            $request->only(['customer_id', 'sale_id', 'date', 'notes']),
            $request->validated('items'),
            $request->user()
        );

        $this->logAudit('create', $note, null, $note->toArray());

        return new DeliveryNoteResource($note->load(['customer', 'sale', 'items', 'creator']));
    }

    public function show(DeliveryNote $delivery_note)
    {
        $this->authorize('view', $delivery_note);

        return new DeliveryNoteResource($delivery_note->load(['customer', 'sale', 'items', 'creator']));
    }

    public function destroy(DeliveryNote $delivery_note)
    {
        $this->authorize('delete', $delivery_note);

        $delivery_note->delete();
        $this->logAudit('delete', $delivery_note);

        return response()->json(['message' => 'Surat jalan berhasil dihapus.']);
    }

    /**
     * PDF version, shareable via WhatsApp. Requires
     * barryvdh/laravel-dompdf — see README-FASE-DE.
     *
     * The filename passed to stream()/download() becomes an HTTP
     * Content-Disposition header value, which CANNOT contain "/" or
     * "\" — but delivery_number itself is formatted like
     * "SJ/JPH390/202609/0001" (Fase D's numbering scheme). Confirmed
     * via manual testing: using delivery_number directly as the
     * filename threw "The filename and the fallback cannot contain
     * the / and \ characters." Fixed by replacing "/" with "-" ONLY
     * in the filename string — the delivery_number shown INSIDE the
     * PDF content itself stays correctly formatted with slashes.
     */
    public function pdf(DeliveryNote $delivery_note)
    {
        $this->authorize('view', $delivery_note);

        $delivery_note->load(['customer', 'sale', 'items', 'company']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.delivery-note', ['note' => $delivery_note]);

        $safeFilename = str_replace('/', '-', $delivery_note->delivery_number);

        return $pdf->stream("surat-jalan-{$safeFilename}.pdf");
    }
}
