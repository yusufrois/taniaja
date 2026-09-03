<div class="glass p-8">
    <h2 class="text-lg font-semibold mb-1 brand">Daftarkan Perusahaan Anda</h2>
    <p class="text-sm lw-muted mb-6">Anda akan menjadi Company Owner setelah mendaftar.</p>

    <form wire:submit="register" class="space-y-4">
        <div>
            <label class="lw-label">Nama Perusahaan</label>
            <input type="text" wire:model="company_name" placeholder="mis. LadangWohIjo" class="lw-input w-full px-3">
            @error('company_name') <p class="text-sm mt-1 lw-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="lw-label">Kode Perusahaan</label>
            <input type="text" wire:model="company_code" placeholder="mis. LWI" class="lw-input w-full px-3">
            @error('company_code') <p class="text-sm mt-1 lw-error">{{ $message }}</p> @enderror
        </div>

        <hr class="my-2" style="border-color:var(--lw-border)">

        <div>
            <label class="lw-label">Nama Anda (Owner)</label>
            <input type="text" wire:model="owner_name" class="lw-input w-full px-3">
            @error('owner_name') <p class="text-sm mt-1 lw-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="lw-label">Email</label>
            <input type="email" wire:model="owner_email" class="lw-input w-full px-3">
            @error('owner_email') <p class="text-sm mt-1 lw-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="lw-label">Password</label>
            <input type="password" wire:model="owner_password" class="lw-input w-full px-3">
            @error('owner_password') <p class="text-sm mt-1 lw-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="lw-label">Konfirmasi Password</label>
            <input type="password" wire:model="owner_password_confirmation" class="lw-input w-full px-3">
        </div>

        <button type="submit" wire:loading.attr="disabled" wire:target="register" class="btn-primary w-full py-2.5">
            <span wire:loading.remove wire:target="register">Daftar</span>
            <span wire:loading wire:target="register">Memproses...</span>
        </button>
    </form>

    <p class="text-center text-sm lw-muted mt-6">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-medium lw-link">Masuk di sini</a>
    </p>
</div>
