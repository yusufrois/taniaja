<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeLoanRequest;
use App\Http\Resources\EmployeeLoanResource;
use App\Models\EmployeeLoan;

class EmployeeLoanController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', EmployeeLoan::class);

        return EmployeeLoanResource::collection(
            EmployeeLoan::with('employee')->orderByDesc('date')->paginate(20)
        );
    }

    public function store(StoreEmployeeLoanRequest $request)
    {
        $this->authorize('create', EmployeeLoan::class);

        $loan = EmployeeLoan::create($request->validated() + [
            'company_id' => $request->user()->company_id,
            'created_by' => $request->user()->id,
        ]);

        $this->logAudit('create', $loan, null, $loan->toArray());

        return new EmployeeLoanResource($loan->load('employee'));
    }

    public function show(EmployeeLoan $employee_loan)
    {
        $this->authorize('view', $employee_loan);

        return new EmployeeLoanResource($employee_loan->load('employee'));
    }
}
