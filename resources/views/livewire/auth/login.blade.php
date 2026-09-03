<div class="glass p-8">
    <h2 class="text-lg font-semibold mb-6 brand">Masuk ke akun Anda</h2>

    <form wire:submit="login" class="space-y-4">
        <div>
            <label class="lw-label">Email</label>
            <input type="email" wire:model="email" autofocus class="lw-input w-full px-3">
            @error('email') <p class="text-sm mt-1 lw-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="lw-label !mb-0">Password</label>
                <a href="{{ route('password.request') }}" class="text-xs lw-link">Lupa password?</a>
            </div>
            <input type="password" wire:model="password" class="lw-input w-full px-3">
            @error('password') <p class="text-sm mt-1 lw-error">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm lw-muted">
            <input type="checkbox" wire:model="remember" class="rounded">
            Ingat saya
        </label>

        <button type="submit" wire:loading.attr="disabled" class="btn-primary w-full py-2.5">
            <span wire:loading.remove wire:target="login">Masuk</span>
            <span wire:loading wire:target="login">Memproses...</span>
        </button>
    </form>

    <p class="text-center text-sm lw-muted mt-6">
        Belum punya akun perusahaan?
        <a href="{{ route('register') }}" class="font-medium lw-link">Daftar di sini</a>
    </p>
</div>
