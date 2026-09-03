<div class="glass p-8">
    <h2 class="text-lg font-semibold mb-1 brand">Lupa Password</h2>
    <p class="text-sm lw-muted mb-6">Masukkan email Anda, kami akan kirim link untuk reset password.</p>

    @if ($status)
        <div class="mb-4 p-3 rounded-md text-sm" style="background:rgba(34,197,94,.10); color:#7bea8d; border:1px solid var(--lw-border);">
            {{ $status }}
        </div>
    @endif

    <form wire:submit="sendResetLink" class="space-y-4">
        <div>
            <label class="lw-label">Email</label>
            <input type="email" wire:model="email" autofocus class="lw-input w-full px-3">
            @error('email') <p class="text-sm mt-1 lw-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" wire:loading.attr="disabled" wire:target="sendResetLink" class="btn-primary w-full py-2.5">
            <span wire:loading.remove wire:target="sendResetLink">Kirim Link Reset</span>
            <span wire:loading wire:target="sendResetLink">Mengirim...</span>
        </button>
    </form>

    <p class="text-center text-sm lw-muted mt-6">
        <a href="{{ route('login') }}" class="font-medium lw-link">Kembali ke Login</a>
    </p>
</div>
