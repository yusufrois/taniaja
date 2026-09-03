<div class="glass p-8">
    <h2 class="text-lg font-semibold mb-6 brand">Buat Password Baru</h2>

    <form wire:submit="resetPassword" class="space-y-4">
        <div>
            <label class="lw-label">Email</label>
            <input type="email" wire:model="email" class="lw-input w-full px-3">
            @error('email') <p class="text-sm mt-1 lw-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="lw-label">Password Baru</label>
            <input type="password" wire:model="password" class="lw-input w-full px-3">
            @error('password') <p class="text-sm mt-1 lw-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="lw-label">Konfirmasi Password</label>
            <input type="password" wire:model="password_confirmation" class="lw-input w-full px-3">
        </div>

        <button type="submit" wire:loading.attr="disabled" wire:target="resetPassword" class="btn-primary w-full py-2.5">
            <span wire:loading.remove wire:target="resetPassword">Reset Password</span>
            <span wire:loading wire:target="resetPassword">Memproses...</span>
        </button>
    </form>
</div>
