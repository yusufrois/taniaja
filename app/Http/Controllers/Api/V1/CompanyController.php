<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;

/**
 * Was seeded in the permission catalog ('company.view'/'company.update')
 * since early in this project, but never actually had a controller
 * behind it — discovered during Fase F's role/permission audit. Now
 * built: a company can view and edit its OWN profile only.
 */
class CompanyController extends Controller
{
    use LogsAudit;

    public function show()
    {
        $company = auth()->user()->company;
        $this->authorize('view', $company);

        return new CompanyResource($company);
    }

    public function update(UpdateCompanyRequest $request)
    {
        $company = $request->user()->company;

        $old = $company->toArray();
        $company->update($request->validated());
        $this->logAudit('update', $company, $old, $company->toArray());

        return new CompanyResource($company);
    }
}
