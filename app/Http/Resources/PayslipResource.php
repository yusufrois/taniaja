<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayslipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payroll_period_id' => $this->payroll_period_id,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee->name),
            'pay_type' => $this->pay_type,
            'gross_amount' => $this->gross_amount,
            'deduction_amount' => $this->deduction_amount,
            'net_amount' => $this->net_amount,
            'detail' => $this->detail,
            'created_at' => $this->created_at,
        ];
    }
}
