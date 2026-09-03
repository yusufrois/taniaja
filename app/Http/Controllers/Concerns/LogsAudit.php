<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait LogsAudit
{
    protected function logAudit(string $action, Model $model, ?array $old = null, ?array $new = null): void
    {
        AuditLog::create([
            'company_id' => auth()->user()?->company_id,
            'user_id' => auth()->id(),
            'action' => $action,
            'model' => get_class($model),
            'model_id' => $model->id,
            'old_value' => $old,
            'new_value' => $new,
        ]);
    }
}
