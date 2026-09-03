<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInputItemRequest;
use App\Http\Requests\UpdateInputItemRequest;
use App\Http\Resources\InputItemResource;
use App\Models\InputItem;

class InputItemController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', InputItem::class);

        return InputItemResource::collection(InputItem::orderBy('name')->paginate(20));
    }

    public function store(StoreInputItemRequest $request)
    {
        $this->authorize('create', InputItem::class);

        $item = InputItem::create($request->validated());
        $this->logAudit('create', $item, null, $item->toArray());

        return new InputItemResource($item);
    }

    public function show(InputItem $input_item)
    {
        $this->authorize('view', $input_item);

        return new InputItemResource($input_item);
    }

    public function update(UpdateInputItemRequest $request, InputItem $input_item)
    {
        $this->authorize('update', $input_item);

        $old = $input_item->toArray();
        $input_item->update($request->validated());
        $this->logAudit('update', $input_item, $old, $input_item->toArray());

        return new InputItemResource($input_item);
    }

    public function destroy(InputItem $input_item)
    {
        $this->authorize('delete', $input_item);

        $input_item->delete();
        $this->logAudit('delete', $input_item);

        return response()->json(['message' => 'Input item berhasil dihapus.']);
    }
}
