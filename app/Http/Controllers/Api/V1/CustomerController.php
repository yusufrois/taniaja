<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\GuardsAgainstReferencedDeletion;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;

class CustomerController extends Controller
{
    use LogsAudit, GuardsAgainstReferencedDeletion;

    public function index()
    {
        $this->authorize('viewAny', Customer::class);

        return CustomerResource::collection(Customer::orderBy('name')->paginate(20));
    }

    public function store(StoreCustomerRequest $request)
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::create($request->validated());
        $this->logAudit('create', $customer, null, $customer->toArray());

        return new CustomerResource($customer);
    }

    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        return new CustomerResource($customer);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $old = $customer->toArray();
        $customer->update($request->validated());
        $this->logAudit('update', $customer, $old, $customer->toArray());

        return new CustomerResource($customer);
    }

    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);

        $this->assertNotReferenced([
            [\App\Models\Sale::class, 'customer_id', $customer->id, 'data Penjualan'],
        ]);

        $customer->delete();
        $this->logAudit('delete', $customer);

        return response()->json(['message' => 'Customer berhasil dihapus.']);
    }
}
