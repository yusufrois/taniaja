<div class="relative" x-data="{ open: @entangle('open') }" wire:poll.15s="$refresh">
    <button @click="open = !open" class="btn-secondary px-2.5 py-1.5 text-sm relative" title="Notifikasi">
        🔔
        @if ($unreadCount > 0)
            <span class="absolute -top-1 -right-1 text-white text-[10px] font-bold rounded-full flex items-center justify-center" style="background:#fb7185; min-width:16px; height:16px; padding:0 3px;">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" @click.outside="open = false" x-transition
         class="absolute right-0 mt-2 w-80"
         style="z-index:60; background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35); display:none">
        <div class="px-4 py-3 flex items-center justify-between" style="border-bottom:1px solid rgba(148,163,184,.1)">
            <p class="font-semibold text-sm">Notifikasi</p>
            @if ($hasRead)
                <button wire:click="clearRead" class="text-xs lw-muted">Bersihkan yang sudah dibaca</button>
            @endif
        </div>

        <div style="max-height:360px; overflow-y:auto">
            @forelse ($notifications as $n)
                @php
                    $warningId = $n->data['warning_id'] ?? null;
                    $needsConfirm = $n->type === 'warning_acknowledged' && $warningId && in_array($warningId, $unconfirmedWarningIds);
                    $needsAcknowledge = $n->type === 'warning_issued' && $warningId && in_array($warningId, $unacknowledgedWarningIds);
                @endphp
                <div class="px-4 py-3" style="border-bottom:1px solid rgba(148,163,184,.06); {{ $n->read_at ? '' : 'background:rgba(74,222,128,.04)' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1">
                            <p class="text-xs font-semibold">{{ $n->title }}</p>
                            <p class="text-xs lw-muted mt-0.5">{{ $n->body }}</p>
                            <p class="text-xs lw-muted mt-1" style="opacity:.7">{{ $n->created_at?->diffForHumans() }}</p>
                        </div>
                        <div class="shrink-0 flex items-center gap-2">
                            @if (! $n->read_at && ! $needsAcknowledge)
                                <button wire:click="markRead({{ $n->id }})" title="Tandai sudah dibaca" class="text-xs" style="color:#5ee878">✓</button>
                            @endif
                            <button wire:click="delete({{ $n->id }})" title="Hapus" class="text-xs" style="color:#fb7185">✕</button>
                        </div>
                    </div>
                    @if ($needsConfirm)
                        <button wire:click="confirmWarning({{ $n->id }})" class="btn-secondary w-full mt-2 py-1 text-xs" style="color:#5ee878">✓ Konfirmasi Peringatan</button>
                    @elseif ($needsAcknowledge)
                        <button wire:click="acknowledgeWarning({{ $n->id }})" class="btn-secondary w-full mt-2 py-1 text-xs" style="color:#5ee878">Sudah Baca</button>
                    @endif
                </div>
            @empty
                <p class="px-4 py-8 text-center text-xs lw-muted">Tidak ada notifikasi.</p>
            @endforelse
        </div>
    </div>
</div>
