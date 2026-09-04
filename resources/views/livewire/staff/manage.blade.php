<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold">👥 Kelola Staf</h1>
            <p class="lw-muted text-sm">Tambah, ubah peran, dan kelola akses staf perusahaan.</p>
        </div>
        @if ($canCreate)
            <button wire:click="openCreate" class="btn-primary px-4 py-2 text-sm">+ Tambah Staf</button>
        @endif
    </div>

    <div class="glass rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left lw-muted text-xs uppercase tracking-wide" style="border-bottom:1px solid rgba(148,163,184,.15)">
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Peran</th>
                        <th class="px-4 py-3">Atasan</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staff as $person)
                        <tr style="border-bottom:1px solid rgba(148,163,184,.08)">
                            <td class="px-4 py-3 font-semibold">{{ $person->name }}</td>
                            <td class="px-4 py-3 lw-muted">{{ $person->email }}</td>
                            <td class="px-4 py-3 lw-muted">{{ $person->roles->first()?->name ?? '-' }}</td>
                            <td class="px-4 py-3 lw-muted">{{ $person->supervisor?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if ($person->status === 'active')
                                    <span class="text-xs font-semibold" style="color:#5ee878">Aktif</span>
                                @else
                                    <span class="text-xs font-semibold" style="color:#fb7185">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                @can('update', $person)
                                    <button wire:click="openEdit({{ $person->id }})" class="btn-secondary px-3 py-1.5 text-xs">Edit</button>
                                    @if ($person->status === 'active')
                                        <button wire:click="confirmSuspend({{ $person->id }})" class="btn-secondary px-3 py-1.5 text-xs" style="color:#fbbf24">Nonaktifkan</button>
                                    @else
                                        <button wire:click="confirmReactivate({{ $person->id }})" class="btn-secondary px-3 py-1.5 text-xs" style="color:#5ee878">Aktifkan</button>
                                    @endif
                                @endcan
                                @if (auth()->user()->hasPermission('user.warn') || auth()->user()->isSupervisorOf($person))
                                    <button wire:click="openWarn({{ $person->id }})" class="btn-secondary px-3 py-1.5 text-xs">Peringatan</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center lw-muted">Belum ada staf lain selain Anda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($staff->hasPages())
            <div class="px-4 py-3" style="border-top:1px solid rgba(148,163,184,.08)">{{ $staff->links() }}</div>
        @endif
    </div>

    {{-- Tambah/Edit Staf --}}
    @if ($showModal)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="closeModal">
            <div class="mx-auto my-8 w-full max-w-lg p-6" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <h2 class="text-lg font-bold mb-4">{{ $editingId ? 'Edit Staf' : 'Tambah Staf Baru' }}</h2>

                @if ($errors->any())
                    <div class="rounded-xl px-4 py-3 mb-4 text-sm" style="background:rgba(251,113,133,.12); border:1px solid rgba(251,113,133,.35)">
                        <p class="font-semibold mb-1" style="color:#fb7185">⚠️ Ada yang perlu diperbaiki:</p>
                        <ul class="list-disc list-inside space-y-0.5" style="color:#fecdd3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="lw-label">Nama Lengkap *</label>
                        <input type="text" wire:model="name" class="lw-input w-full px-3">
                    </div>
                    <div>
                        <label class="lw-label">Email *</label>
                        <input type="email" wire:model="email" class="lw-input w-full px-3">
                    </div>
                    <div>
                        <label class="lw-label">{{ $editingId ? 'Password Baru (kosongkan kalau tidak diubah)' : 'Password *' }}</label>
                        <input type="password" wire:model="password" class="lw-input w-full px-3">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="lw-label">Peran *</label>
                            <select wire:model="role_id" class="lw-input w-full px-3">
                                <option value="">-- Pilih --</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="lw-label">Atasan (opsional)</label>
                            <select wire:model="supervisor_id" class="lw-input w-full px-3">
                                <option value="">-- Tidak ada --</option>
                                @foreach ($supervisors as $sup)
                                    @if (! $editingId || $sup->id !== $editingId)
                                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeModal" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                        <button type="submit" class="btn-primary px-4 py-2 text-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Konfirmasi Nonaktifkan --}}
    @if ($confirmingSuspendId)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="$set('confirmingSuspendId', null)">
            <div class="mx-auto my-8 w-full max-w-sm p-6 text-center" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <p class="mb-4">Yakin ingin menonaktifkan staf ini? Dia tidak akan bisa login sampai diaktifkan lagi.</p>
                <div class="flex justify-center gap-2">
                    <button wire:click="$set('confirmingSuspendId', null)" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                    <button wire:click="suspend" class="btn-primary px-4 py-2 text-sm" style="background:linear-gradient(135deg,#fbbf24,#f59e0b)">Nonaktifkan</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Konfirmasi Aktifkan --}}
    @if ($confirmingReactivateId)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="$set('confirmingReactivateId', null)">
            <div class="mx-auto my-8 w-full max-w-sm p-6 text-center" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <p class="mb-4">Aktifkan kembali staf ini?</p>
                <div class="flex justify-center gap-2">
                    <button wire:click="$set('confirmingReactivateId', null)" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                    <button wire:click="reactivate" class="btn-primary px-4 py-2 text-sm">Aktifkan</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Peringatan --}}
    @if ($warningTargetId && $warningTarget)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="closeWarn">
            <div class="mx-auto my-8 w-full max-w-lg p-6" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <h2 class="text-lg font-bold mb-1">Peringatan untuk {{ $warningTarget->name }}</h2>
                <p class="lw-muted text-xs mb-4">Riwayat peringatan sebelumnya: {{ $warningTarget->warnings->count() }}</p>

                @if ($warningTarget->warnings->isNotEmpty())
                    <div class="mb-4 space-y-2" style="max-height:150px; overflow-y:auto">
                        @foreach ($warningTarget->warnings as $w)
                            <div class="text-xs rounded-lg px-3 py-2 flex items-start justify-between gap-2" style="background:rgba(251,191,36,.08); border:1px solid rgba(251,191,36,.2)">
                                <div>
                                    <p>{{ $w->reason }}</p>
                                    <p class="lw-muted mt-1">{{ $w->created_at?->translatedFormat('d M Y H:i') }} — oleh {{ $w->issuer?->name }}</p>
                                    @if ($w->confirmed_at)
                                        <p class="mt-1" style="color:#5ee878">✓ Dikonfirmasi oleh {{ $w->confirmedBy?->name }}</p>
                                    @elseif ($w->acknowledged_at)
                                        <p class="mt-1" style="color:#60a5fa">Staf sudah baca — menunggu konfirmasi Anda</p>
                                    @else
                                        <p class="mt-1 lw-muted">Belum dibaca staf</p>
                                    @endif
                                </div>
                                <div class="shrink-0 flex flex-col items-end gap-1">
                                    @if ($w->acknowledged_at && ! $w->confirmed_at)
                                        <button wire:click="confirmWarning({{ $w->id }})" class="btn-secondary px-2 py-1 text-xs" style="color:#5ee878">Konfirmasi</button>
                                    @endif
                                    <button wire:click="deleteWarning({{ $w->id }})" wire:confirm="Hapus peringatan ini?" class="text-xs" style="color:#fb7185">✕</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @error('warningReason') <p class="lw-error text-xs mb-2">{{ $message }}</p> @enderror

                <textarea wire:model="warningReason" rows="3" class="lw-input w-full px-3 py-2" placeholder="Alasan peringatan..."></textarea>

                <div class="flex justify-end gap-2 pt-3">
                    <button type="button" wire:click="closeWarn" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                    <button wire:click="submitWarning" class="btn-primary px-4 py-2 text-sm" style="background:linear-gradient(135deg,#fbbf24,#f59e0b)">Kirim Peringatan</button>
                </div>
            </div>
        </div>
    @endif
</div>
