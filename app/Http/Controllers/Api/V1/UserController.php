<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\StoreUserWarningRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserWarningResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * "Add a staff member to MY company" (Owner managing their own team,
 * Fase A3) AND — as of Fase A5 — "Super Admin manages ANY user across
 * ANY company", including suspending/warning a Company Owner who
 * violates platform rules. Two different people use this same
 * controller: an ordinary Owner (company-scoped) and the platform's
 * Super Admin (unscoped, company_id === null on their own account).
 *
 * IMPORTANT: User does NOT use the BelongsToCompany trait (Super Admin
 * needs unscoped access), so tenant scoping here is hand-written via
 * assertSameCompanyOrSuperAdmin() below — NOT automatic. Every method
 * that operates on a specific $user MUST call it first. Forgetting
 * this on any method reopens exactly the cross-tenant leak Fase A3's
 * tests were written to catch — see UserManagementTest and
 * UserWarningSuspendTest, both of which will fail if this regresses.
 *
 * Fase A5 fixed a real bug: before this, a Super Admin (whose OWN
 * company_id is null) would get 404 from every one of these checks,
 * since `null !== $target->company_id` is always true — meaning a
 * Super Admin could not manage ANY user at all, despite being designed
 * to bypass every permission check via User::isSuperAdmin(). The fix:
 * skip the company match entirely when the ACTING user is Super Admin.
 */
class UserController extends Controller
{
    use LogsAudit;

    /**
     * The one piece of tenant-scoping logic in this controller — every
     * method touching a specific $user must call this before doing
     * anything else with it. Returns silently (allow) for Super Admin;
     * 404s for anyone else whose company doesn't match the target's.
     */
    private function assertSameCompanyOrSuperAdmin(User $target): void
    {
        if (auth()->user()->isSuperAdmin()) {
            return;
        }

        abort_if($target->company_id !== auth()->user()->company_id, 404);
    }

    /**
     * For a normal Owner: lists only their own company's staff (as
     * before). For Super Admin: lists EVERY user across EVERY company
     * by default, optionally narrowed with ?company_id=X — this is how
     * a Super Admin would find "the Owner of Company X" to act on.
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $query = User::with(['roles', 'supervisor'])->orderBy('name');

        if (auth()->user()->isSuperAdmin()) {
            if (request()->filled('company_id')) {
                $query->where('company_id', request()->integer('company_id'));
            }
        } else {
            $query->where('company_id', auth()->user()->company_id);
        }

        return UserResource::collection($query->paginate(20));
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);

        $user = User::create([
            'company_id' => $request->user()->company_id, // never trust client input for this
            'supervisor_id' => $request->validated('supervisor_id'),
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'status' => 'active',
        ]);

        $user->roles()->attach($request->validated('role_id'));

        $this->logAudit('create', $user, null, ['name' => $user->name, 'email' => $user->email]);

        return new UserResource($user->load(['roles', 'supervisor']));
    }

    public function show(User $user)
    {
        $this->assertSameCompanyOrSuperAdmin($user);

        $this->authorize('view', $user);

        return new UserResource($user->load(['roles', 'supervisor']));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->assertSameCompanyOrSuperAdmin($user);

        $old = $user->only(['name', 'status', 'supervisor_id']);

        $user->update($request->safe()->only(['name', 'status', 'supervisor_id']));

        if ($request->filled('role_id')) {
            // Replace the assignment rather than accumulate — matches
            // this feature's "one primary role per user" scope (see
            // StoreUserRequest's docblock reasoning).
            $user->roles()->sync([$request->validated('role_id')]);
        }

        $this->logAudit('update', $user, $old, $user->only(['name', 'status', 'supervisor_id']));

        return new UserResource($user->load(['roles', 'supervisor']));
    }

    /**
     * Deactivates rather than hard-deletes — a staff account is tied to
     * historical records (created_by on Purchase/Sale/Expense, audit
     * logs, etc.), so removing the row would orphan that history. Soft
     * delete AND status=inactive together: soft delete keeps them out
     * of normal listings, status=inactive blocks login even if the row
     * is ever restored — same "void, don't erase" principle as
     * financial transactions elsewhere in this app (Section 30).
     */
    public function destroy(User $user)
    {
        $this->assertSameCompanyOrSuperAdmin($user);

        $this->authorize('delete', $user);

        $user->update(['status' => 'inactive']);
        $user->delete();

        $this->logAudit('delete', $user);

        return response()->json(['message' => 'User berhasil dinonaktifkan.']);
    }

    /**
     * PATCH /users/{id}/suspend — a lighter-weight alternative to
     * destroy(): sets status=inactive WITHOUT soft-deleting, so the
     * user stays visible in index() (as "inactive") and can be
     * reactivated later. destroy() (soft delete + inactive) is for
     * permanently removing someone; suspend() is for "temporarily
     * blocked while we sort this out" — including, since Fase A5, a
     * Super Admin suspending a Company Owner's account for violating
     * platform rules, not just an Owner suspending their own staff.
     */
    public function suspend(User $user)
    {
        $this->assertSameCompanyOrSuperAdmin($user);

        // Reuses the 'update' permission for a same-company Owner. A
        // Super Admin always passes $this->authorize() regardless
        // (User::hasPermission() short-circuits true for them), so this
        // check never blocks the Super Admin path.
        $this->authorize('update', $user);

        $old = $user->only('status');
        $user->update(['status' => 'inactive']);

        $this->logAudit('user.suspend', $user, $old, ['status' => 'inactive']);

        return new UserResource($user->load(['roles', 'supervisor']));
    }

    public function reactivate(User $user)
    {
        $this->assertSameCompanyOrSuperAdmin($user);

        $this->authorize('update', $user);

        $old = $user->only('status');
        $user->update(['status' => 'active']);

        $this->logAudit('user.reactivate', $user, $old, ['status' => 'active']);

        return new UserResource($user->load(['roles', 'supervisor']));
    }

    /**
     * GET /users/{id}/warnings — history of warnings issued to this
     * user, e.g. so an Owner (or Super Admin, for a Company Owner) can
     * see "this is the 3rd warning" before deciding to suspend.
     */
    public function warnings(User $user)
    {
        $this->assertSameCompanyOrSuperAdmin($user);

        abort_unless(auth()->user()->hasPermission('user.warn'), 403);

        // Ordered by id (not created_at alone) — two warnings issued
        // within the same second would otherwise tie on created_at's
        // second-level precision and come back in an unpredictable
        // order. id is always strictly increasing, so it's a reliable
        // "newest first" tiebreaker regardless of timestamp precision.
        $warnings = $user->warnings()->with('issuer')->orderByDesc('id')->get();

        return UserWarningResource::collection($warnings);
    }

    /**
     * POST /users/{id}/warnings — issue a new warning. Deliberately a
     * SEPARATE permission ('user.warn', checked inside
     * StoreUserWarningRequest) from 'user.update' — an Owner may want
     * a Manager to be able to warn their own team without granting
     * full staff-management rights. Super Admin always has this
     * permission via the isSuperAdmin() bypass.
     */
    public function warn(StoreUserWarningRequest $request, User $user)
    {
        $this->assertSameCompanyOrSuperAdmin($user);

        $warning = $user->warnings()->create([
            'company_id' => $user->company_id,
            'issued_by' => $request->user()->id,
            'reason' => $request->validated('reason'),
        ]);

        $this->logAudit('user.warn', $user, null, ['reason' => $warning->reason]);

        return new UserWarningResource($warning->load('issuer'));
    }
}
