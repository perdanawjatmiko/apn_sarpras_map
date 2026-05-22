<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\Koperasi;
use App\Models\Pengaduan;
use App\Models\Province;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HelpdeskTest extends TestCase
{
    use RefreshDatabase;

    public function test_helpdesk_page_can_be_rendered()
    {
        $province = Province::create(['name' => 'Jawa Tengah', 'code' => '33']);
        Koperasi::create([
            'name' => 'Koperasi Contoh',
            'province_id' => $province->id,
        ]);

        $this->get(route('helpdesk.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('helpdesk')
                ->where('provinces.0.name', 'Jawa Tengah'));
    }

    public function test_helpdesk_koperasi_options_are_filtered_by_region()
    {
        $province = Province::create(['name' => 'Jawa Tengah', 'code' => '33']);
        $city = City::create(['name' => 'Semarang', 'code' => '74', 'full_code' => '33.74', 'province_id' => $province->id]);
        $district = District::create(['name' => 'Tembalang', 'code' => '01', 'full_code' => '33.74.01', 'city_id' => $city->id]);
        $village = Village::create(['name' => 'Bulusan', 'code' => '1001', 'full_code' => '33.74.01.1001', 'district_id' => $district->id]);
        $matching = Koperasi::create([
            'name' => 'Koperasi Cocok',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'district_id' => $district->id,
            'village_id' => $village->id,
        ]);
        Koperasi::create([
            'name' => 'Koperasi Lain',
            'province_id' => $province->id,
        ]);

        $this->getJson(route('helpdesk.koperasis', [
            'province_id' => $province->id,
            'city_id' => $city->id,
            'district_id' => $district->id,
            'village_id' => $village->id,
        ]))
            ->assertOk()
            ->assertExactJson([
                [
                    'id' => $matching->id,
                    'name' => 'Koperasi Cocok',
                    'user_id' => null,
                ],
            ]);
    }

    public function test_helpdesk_registers_pic_and_assigns_it_to_koperasi()
    {
        config(['auth.helpdesk_default_password' => 'kdkmp1061']);

        $koperasi = Koperasi::create(['name' => 'Koperasi Contoh']);

        $this->post(route('helpdesk.store'), [
            'koperasi_id' => $koperasi->id,
            'name' => 'PIC Contoh',
            'phone' => '081234567890',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $user = User::where('phone', '081234567890')->firstOrFail();

        $this->assertSame('PIC Contoh', $user->name);
        $this->assertNull($user->email);
        $this->assertTrue(Hash::check('kdkmp1061', $user->password));
        $this->assertSame($user->id, $koperasi->refresh()->user_id);
    }

    public function test_helpdesk_user_can_login_and_view_dashboard()
    {
        $user = User::factory()->create([
            'phone' => '081234567890',
            'password' => Hash::make('secret123'),
        ]);

        $this->post(route('helpdesk.login'), [
            'phone' => '081234567890',
            'password' => 'secret123',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('helpdesk.dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->get(route('helpdesk.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('helpdesk/dashboard')
                ->where('user.phone', '081234567890')
                ->where('stats.open', 0)
                ->has('reports', 0));
    }

    public function test_helpdesk_user_can_login_with_email()
    {
        $user = User::factory()->create([
            'email' => 'pic@example.com',
            'phone' => '081234567890',
            'password' => Hash::make('secret123'),
        ]);

        $this->post(route('helpdesk.login'), [
            'phone' => 'pic@example.com',
            'password' => 'secret123',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('helpdesk.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_helpdesk_dashboard_requires_login()
    {
        $this->get(route('helpdesk.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_helpdesk_user_can_create_pengaduan_with_photo()
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'PIC Koperasi',
            'phone' => '081234567890',
        ]);
        $province = Province::create(['name' => 'Jawa Tengah', 'code' => '33']);
        $city = City::create(['name' => 'Semarang', 'code' => '74', 'full_code' => '33.74', 'province_id' => $province->id]);
        $koperasi = Koperasi::create([
            'name' => 'Koperasi Contoh',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('helpdesk.pengaduans.store'), [
                'title' => 'CCTV mati',
                'detail' => 'CCTV tidak menyala sejak pagi.',
                'attachment' => UploadedFile::fake()->image('cctv.jpg'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $pengaduan = Pengaduan::where('title', 'CCTV mati')->firstOrFail();

        $this->assertSame($user->id, $pengaduan->reporter_user_id);
        $this->assertSame('PIC Koperasi', $pengaduan->reporter_name);
        $this->assertSame('081234567890', $pengaduan->reporter_phone);
        $this->assertSame($koperasi->id, $pengaduan->koperasi_id);
        $this->assertSame($province->id, $pengaduan->province_id);
        $this->assertSame($city->id, $pengaduan->city_id);
        $this->assertNotNull($pengaduan->attachment_path);
        Storage::disk('public')->assertExists($pengaduan->attachment_path);
    }
}
