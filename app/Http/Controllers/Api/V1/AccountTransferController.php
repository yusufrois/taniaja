<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountTransferRequest;
use App\Http\Resources\AccountTransferResource;
use App\Models\AccountTransfer;

class AccountTransferController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', AccountTransfer::class);

        return AccountTransferResource::collection(
            AccountTransfer::with(['fromAccount', 'toAccount', 'creator'])->orderByDesc('date')->paginate(20)
        );
    }

    public function store(StoreAccountTransferRequest $request)
    {
        $this->authorize('create', AccountTransfer::class);

        $transfer = AccountTransfer::create($request->validated() + [
            'company_id' => $request->user()->company_id,
            'created_by' => $request->user()->id,
        ]);

        $this->logAudit('create', $transfer, null, $transfer->toArray());

        return new AccountTransferResource($transfer->load(['fromAccount', 'toAccount', 'creator']));
    }

    public function show(AccountTransfer $account_transfer)
    {
        $this->authorize('view', $account_transfer);

        return new AccountTransferResource($account_transfer->load(['fromAccount', 'toAccount', 'creator']));
    }
}
