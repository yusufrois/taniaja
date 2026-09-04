<?php

namespace Tests\Feature\Livewire;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bug report: bel notifikasi hilang dari header (basis layout yang
 * saya pakai untuk paket sebelumnya ternyata dari sebelum bel
 * dibangun — kesalahan yang sama seperti kasus web.reports dulu).
 * Juga menambahkan wire:poll supaya atasan tidak perlu pindah
 * halaman untuk melihat notifikasi baru dari staf yang "Sudah Baca".
 */
class NotificationBellPresenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_bell_renders_on_the_dashboard(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Notifikasi');
        $response->assertSee('wire:poll.15s', false);
    }
}
