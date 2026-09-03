<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * Was seeded in the permission catalog ('audit.view') since early in
 * this project, but never actually had a controller behind it —
 * discovered during Fase F's role/permission audit. AuditLog rows have
 * been accumulating this whole time (LogsAudit trait, used by nearly
 * every controller) with no way to actually READ them until now.
 *
 * AuditLog does NOT use BelongsToCompany (it predates that trait /
 * was never retrofitted with it), so company scoping is done manually
 * here — same reasoning as UserController (RBAC Fase A5).
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::with('user')
            ->where('company_id', auth()->user()->company_id)
            ->orderByDesc('id');

        if ($request->filled('model')) {
            $query->where('model', $request->query('model'));
        }
        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        return AuditLogResource::collection($query->paginate(30));
    }
}
