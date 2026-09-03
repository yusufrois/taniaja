<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChartOfAccountRequest;
use App\Http\Resources\ChartOfAccountResource;
use App\Models\ChartOfAccount;
use App\Services\Accounting\StandardChartOfAccountsSeeder;

class ChartOfAccountController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', ChartOfAccount::class);

        return ChartOfAccountResource::collection(
            ChartOfAccount::where('is_active', true)->orderBy('code')->get()
        );
    }

    public function store(StoreChartOfAccountRequest $request)
    {
        $this->authorize('create', ChartOfAccount::class);

        $account = ChartOfAccount::create($request->validated() + ['company_id' => $request->user()->company_id]);
        $this->logAudit('create', $account, null, $account->toArray());

        return new ChartOfAccountResource($account);
    }

    public function show(ChartOfAccount $chart_of_account)
    {
        $this->authorize('view', $chart_of_account);

        return new ChartOfAccountResource($chart_of_account);
    }

    /**
     * For companies that existed BEFORE Fase L — new companies get
     * this automatically at registration (CompanyRegistrationService),
     * but existing ones need to trigger it once manually. Idempotent —
     * safe to call even if some/all standard accounts already exist.
     */
    public function seedDefaults(StandardChartOfAccountsSeeder $seeder)
    {
        $this->authorize('create', ChartOfAccount::class);

        $seeder->seedFor(auth()->user()->company);

        return ChartOfAccountResource::collection(
            ChartOfAccount::where('company_id', auth()->user()->company_id)->orderBy('code')->get()
        );
    }
}
