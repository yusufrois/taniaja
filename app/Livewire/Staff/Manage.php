<?php

namespace App\Livewire\Staff;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Roadmap tambahan — "Kelola Staf" (tambah/edit/suspend/peringatan),
 * satu-satunya cara membuat akun Finance/Supervisor/Worker lewat web
 * sebelum ini (sebelumnya cuma bisa lewat API/Postman langsung).
 * Reuses UserPolicy/StoreUserRequest/UpdateUserRequest's EXACT rules
 * — not reimplemented — same principle as every other Phase 9/10 page.
 */
#[Layout('layouts.app')]
class Manage extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public ?int $role_id = null;
    public ?int $supervisor_id = null;

    public ?int $confirmingSuspendId = null;
    public ?int $confirmingReactivateId = null;

    public ?int $warningTargetId = null;
    public string $warningReason = '';

    protected function rules(): array
    {
        $companyId = auth()->user()->company_id;

        $passwordRule = $this->editingId ? ['nullable', 'string', 'min:8'] : ['required', 'string', 'min:8'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email',
                \Illuminate\Validation\Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'password' => $passwordRule,
            'role_id' => ['required', \Illuminate\Validation\Rule::exists('roles', 'id')->where('company_id', $companyId)],
            'supervisor_id' => ['nullable', \Illuminate\Validation\Rule::exists('users', 'id')->where('company_id', $companyId)],
        ];
    }

    public function mount(): void
    {
        // Roadmap tambahan — previously ONLY 'user.view' (Owner-only
        // in the Role Matrix) could open this page at all, meaning a
        // Manager/Supervisor could never reach the "Peringatan"
        // button even for their OWN subordinates. Now also lets in
        // anyone who supervises at least one person — the staff list
        // below is filtered to just their own team in that case (see
        // render()), never the whole company.
        $canFullyManage = auth()->user()->can('viewAny', User::class);
        $hasSubordinates = auth()->user()->subordinates()->exists();

        abort_unless($canFullyManage || $hasSubordinates, 403);
    }

    public function openCreate(): void
    {
        $this->authorize('create', User::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role_id = $user->roles->first()?->id;
        $this->supervisor_id = $user->supervisor_id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $this->authorize('update', $user);

            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'supervisor_id' => $data['supervisor_id'],
            ]);
            if (! empty($data['password'])) {
                $user->update(['password' => Hash::make($data['password'])]);
            }
            $user->roles()->sync([$data['role_id']]);
        } else {
            $this->authorize('create', User::class);

            $user = User::create([
                'company_id' => auth()->user()->company_id,
                'supervisor_id' => $data['supervisor_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => 'active',
            ]);
            $user->roles()->attach($data['role_id']);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmSuspend(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);
        $this->confirmingSuspendId = $id;
    }

    public function suspend(): void
    {
        $user = User::findOrFail($this->confirmingSuspendId);
        $this->authorize('update', $user);
        $user->update(['status' => 'inactive']);
        $this->confirmingSuspendId = null;
    }

    public function confirmReactivate(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);
        $this->confirmingReactivateId = $id;
    }

    public function reactivate(): void
    {
        $user = User::findOrFail($this->confirmingReactivateId);
        $this->authorize('update', $user);
        $user->update(['status' => 'active']);
        $this->confirmingReactivateId = null;
    }

    /**
     * Roadmap tambahan — "semua atasan yang punya anak buah bisa
     * kirim peringatan ke bawahannya", not just roles with the
     * blanket 'user.warn' permission — same rule as the API
     * (StoreUserWarningRequest/UserController).
     */
    private function assertCanWarn(User $target): void
    {
        $canWarn = auth()->user()->hasPermission('user.warn') || auth()->user()->isSupervisorOf($target);
        abort_unless($canWarn, 403);
    }

    public function openWarn(int $id): void
    {
        $user = User::findOrFail($id);
        $this->assertCanWarn($user);
        $this->warningTargetId = $id;
        $this->warningReason = '';
        $this->resetErrorBag();
    }

    public function submitWarning(): void
    {
        $user = User::findOrFail($this->warningTargetId);
        $this->assertCanWarn($user);

        $this->validate(['warningReason' => ['required', 'string', 'max:1000']], [], ['warningReason' => 'alasan']);

        $warning = $user->warnings()->create([
            'company_id' => $user->company_id,
            'issued_by' => auth()->id(),
            'reason' => $this->warningReason,
        ]);

        // Roadmap tambahan — bug report: bel notifikasi bilang "tidak
        // ada" padahal Dashboard menampilkan Peringatan. Sekarang
        // setiap Peringatan juga otomatis jadi Notifikasi.
        \App\Models\Notification::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'type' => 'warning_issued',
            'title' => 'Anda mendapat peringatan',
            'body' => $warning->reason,
            'data' => ['warning_id' => $warning->id],
        ]);

        $this->warningTargetId = null;
        $this->warningReason = '';
    }

    public function closeWarn(): void
    {
        $this->warningTargetId = null;
        $this->warningReason = '';
        $this->resetErrorBag();
    }

    /** Roadmap tambahan — "peringatan bisa dihapus, biar tidak menumpuk". */
    public function deleteWarning(int $warningId): void
    {
        $warning = \App\Models\UserWarning::findOrFail($warningId);
        $this->assertCanWarn($warning->user);
        abort_if($warning->user_id !== $this->warningTargetId, 404);
        $warning->delete();
    }

    /**
     * Roadmap tambahan — atasan confirms the staff member already
     * acknowledged ("Sudah Baca") their warning. Only after THIS does
     * it stop appearing on the staff member's own Dashboard.
     */
    public function confirmWarning(int $warningId): void
    {
        $warning = \App\Models\UserWarning::findOrFail($warningId);
        $this->assertCanWarn($warning->user);
        abort_if($warning->user_id !== $this->warningTargetId, 404);
        abort_if(! $warning->acknowledged_at, 422);
        $warning->update(['confirmed_at' => now(), 'confirmed_by' => auth()->id()]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role_id = null;
        $this->supervisor_id = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $companyId = auth()->user()->company_id;
        $user = auth()->user();
        $canFullyManage = $user->can('viewAny', User::class);

        $staffQuery = User::with(['roles', 'supervisor'])->where('company_id', $companyId);
        if (! $canFullyManage) {
            // Limited access (supervisor without full user.view) —
            // only ever see their own direct subordinates, never the
            // whole company's staff list.
            $staffQuery->where('supervisor_id', $user->id);
        }

        return view('livewire.staff.manage', [
            'staff' => $staffQuery->orderBy('name')->paginate(10),
            'roles' => Role::where('company_id', $companyId)->orderBy('name')->get(),
            'supervisors' => User::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get(),
            'warningTarget' => $this->warningTargetId
                ? User::with([
                    'warnings' => fn ($q) => $q->orderByDesc('id'),
                    'warnings.issuer', 'warnings.confirmedBy',
                ])->find($this->warningTargetId)
                : null,
            'canCreate' => $canFullyManage && $user->can('create', User::class),
        ]);
    }
}
