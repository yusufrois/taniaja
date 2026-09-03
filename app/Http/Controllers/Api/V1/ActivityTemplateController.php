<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityTemplateItemRequest;
use App\Http\Requests\StoreActivityTemplateRequest;
use App\Http\Requests\UpdateActivityTemplateRequest;
use App\Http\Resources\ActivityTemplateItemResource;
use App\Http\Resources\ActivityTemplateResource;
use App\Models\ActivityTemplate;
use App\Models\ActivityTemplateItem;

class ActivityTemplateController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', ActivityTemplate::class);

        return ActivityTemplateResource::collection(
            ActivityTemplate::with(['variety', 'items'])->orderBy('name')->paginate(20)
        );
    }

    public function store(StoreActivityTemplateRequest $request)
    {
        $this->authorize('create', ActivityTemplate::class);

        $template = ActivityTemplate::create($request->validated());
        $this->logAudit('create', $template, null, $template->toArray());

        return new ActivityTemplateResource($template->load('variety'));
    }

    public function show(ActivityTemplate $activity_template)
    {
        $this->authorize('view', $activity_template);

        return new ActivityTemplateResource($activity_template->load(['variety', 'items']));
    }

    public function update(UpdateActivityTemplateRequest $request, ActivityTemplate $activity_template)
    {
        $this->authorize('update', $activity_template);

        $old = $activity_template->toArray();
        $activity_template->update($request->validated());
        $this->logAudit('update', $activity_template, $old, $activity_template->toArray());

        return new ActivityTemplateResource($activity_template->load(['variety', 'items']));
    }

    public function destroy(ActivityTemplate $activity_template)
    {
        $this->authorize('delete', $activity_template);

        $activity_template->delete();
        $this->logAudit('delete', $activity_template);

        return response()->json(['message' => 'Template berhasil dihapus.']);
    }

    /**
     * Nested resource: POST /activity-templates/{activity_template}/items
     * Kept inside this controller rather than a separate ItemController
     * because an item never exists independently of its template — same
     * reasoning as Sale Items living under Sales in the architecture doc.
     */
    public function storeItem(StoreActivityTemplateItemRequest $request, ActivityTemplate $activity_template)
    {
        $item = $activity_template->items()->create(
            $request->validated() + ['company_id' => $activity_template->company_id]
        );

        return new ActivityTemplateItemResource($item);
    }

    public function destroyItem(ActivityTemplate $activity_template, ActivityTemplateItem $item)
    {
        $this->authorize('update', $activity_template);

        abort_unless($item->activity_template_id === $activity_template->id, 404);

        $item->delete();

        return response()->json(['message' => 'Item template berhasil dihapus.']);
    }
}
