<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;

class EmployeeController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', Employee::class);

        return EmployeeResource::collection(Employee::orderBy('name')->paginate(20));
    }

    public function store(StoreEmployeeRequest $request)
    {
        $this->authorize('create', Employee::class);

        $employee = Employee::create($request->validated() + [
            'company_id' => $request->user()->company_id,
        ]);
        $this->logAudit('create', $employee, null, $employee->toArray());

        return new EmployeeResource($employee);
    }

    public function show(Employee $employee)
    {
        $this->authorize('view', $employee);

        return new EmployeeResource($employee);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $this->authorize('update', $employee);

        $old = $employee->toArray();
        $employee->update($request->validated());
        $this->logAudit('update', $employee, $old, $employee->toArray());

        return new EmployeeResource($employee);
    }

    public function destroy(Employee $employee)
    {
        $this->authorize('delete', $employee);

        $employee->delete();
        $this->logAudit('delete', $employee);

        return response()->json(['message' => 'Data pegawai berhasil dihapus.']);
    }
}
