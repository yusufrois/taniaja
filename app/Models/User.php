<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    // NOTE: User itself does NOT use BelongsToCompany's global scope,
    // because Super Admin (company_id = null) and cross-tenant admin
    // screens need to query users directly. Tenant filtering for user
    // management is done explicitly in UserService/Policy instead.

    protected $fillable = [
        'company_id', 'supervisor_id', 'name', 'email', 'password', 'status',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Direct reports only — the "bawahan langsung" of this user. Does
     * NOT recurse further down (a subordinate's own subordinates are
     * not included) — kept as a simple one-level hierarchy per the
     * migration's note.
     */
    public function subordinates()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    public function warnings()
    {
        return $this->hasMany(UserWarning::class);
    }

    /**
     * The Employee record this login account is linked to, if any —
     * see Employee's migration docblock for why User/Employee are
     * separate (not every worker has a login account).
     */
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function tasksAssignedToMe()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function tasksIAssigned()
    {
        return $this->hasMany(Task::class, 'assigned_by');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class)->orderByDesc('id');
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles()->where('slug', $slug)->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function hasPermission(string $permissionName): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->where('name', $permissionName))
            ->exists();
    }

    /**
     * True if the user can see EVERY record of this module (holds the
     * full "{module}.view" permission) — used by controllers to decide
     * whether to scope a list query to "own records only" or not.
     * Part of the RBAC roadmap's Fase A ("data milik sendiri").
     */
    public function canViewAllOf(string $module): bool
    {
        return $this->hasPermission("{$module}.view");
    }

    /**
     * True only when the user has the LESSER "{module}.view_own"
     * permission WITHOUT the full "{module}.view" — i.e. they should
     * only ever see records they personally created. A user who has
     * BOTH (unusual, but possible if a role was assigned both) is
     * treated as full-view, since "view" is strictly more permissive
     * and there is no reason to additionally restrict them.
     */
    public function canViewOwnOnlyOf(string $module): bool
    {
        return ! $this->hasPermission("{$module}.view")
            && ! $this->hasPermission("{$module}.view_team")
            && $this->hasPermission("{$module}.view_own");
    }

    /**
     * "{module}.view_team" — RBAC roadmap Fase A2 ("Manager bisa lihat
     * data semua timnya", "SPV bisa lihat bawahannya"). Sits BETWEEN
     * view (everyone) and view_own (self only): true only when the user
     * has view_team but NOT the full view — a user with both is treated
     * as full-view, same precedence reasoning as canViewOwnOnlyOf().
     */
    public function canViewTeamOnlyOf(string $module): bool
    {
        return ! $this->hasPermission("{$module}.view")
            && $this->hasPermission("{$module}.view_team");
    }

    /**
     * Self + direct subordinates' IDs — what a view_team-restricted
     * user's list query gets filtered to (records created by any of
     * these users). Deliberately does NOT recurse into subordinates'
     * own subordinates — see subordinates() for why.
     */
    public function teamUserIds(): array
    {
        return $this->subordinates()->pluck('id')->push($this->id)->all();
    }

    /**
     * Roadmap tambahan — "semua atasan yang punya anak buah bisa
     * kirim notifikasi/peringatan ke bawahannya, bukan cuma role
     * yang punya izin user.warn penuh". Direct supervision only (not
     * recursive down the chain) — matches teamUserIds()'s existing
     * "doesn't recurse into subordinates' own subordinates" choice.
     */
    public function isSupervisorOf(User $user): bool
    {
        return $user->supervisor_id === $this->id;
    }
}
