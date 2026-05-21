<?php

namespace Tests\Feature;

use App\Models\Koperasi;
use App\Models\KoperasiSarpras;
use App\Models\Sarpras;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_lists_installed_sarpras_totals_by_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $installed = Status::firstOrCreate(['name' => 'terpasang']);
        $shipping = Status::firstOrCreate(['name' => 'pengiriman']);
        $cctv = Sarpras::create(['name' => 'CCTV', 'slug' => 'cctv']);
        $printer = Sarpras::create(['name' => 'Printer', 'slug' => 'printer']);
        $display = Sarpras::create(['name' => 'Display', 'slug' => 'display']);
        $firstKoperasi = Koperasi::create(['name' => 'Koperasi Satu']);
        $secondKoperasi = Koperasi::create(['name' => 'Koperasi Dua']);

        KoperasiSarpras::create([
            'koperasi_id' => $firstKoperasi->id,
            'sarpras_id' => $cctv->id,
            'status_id' => $installed->id,
        ]);
        KoperasiSarpras::create([
            'koperasi_id' => $secondKoperasi->id,
            'sarpras_id' => $cctv->id,
            'status_id' => $installed->id,
        ]);
        KoperasiSarpras::create([
            'koperasi_id' => $firstKoperasi->id,
            'sarpras_id' => $printer->id,
            'status_id' => $shipping->id,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('installed_by_sarpras.0.name', 'CCTV')
                ->where('installed_by_sarpras.0.installed_total', 2)
                ->where('installed_by_sarpras.1.name', 'Display')
                ->where('installed_by_sarpras.1.installed_total', 0)
                ->where('installed_by_sarpras.2.name', 'Printer')
                ->where('installed_by_sarpras.2.installed_total', 0));
    }
}
