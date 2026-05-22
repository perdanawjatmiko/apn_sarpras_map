<?php

namespace Tests\Feature\Admin;

use App\Models\City;
use App\Models\Koperasi;
use App\Models\Pengaduan;
use App\Models\PengaduanCategory;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PengaduanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_pengaduans()
    {
        $this->actingAs(User::factory()->create());

        Pengaduan::create([
            'reporter_name' => 'Pelapor Satu',
            'reporter_phone' => '081234567890',
            'title' => 'CCTV mati',
            'detail' => 'CCTV tidak menyala.',
        ]);

        $this->get(route('admin.pengaduans.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/index')
                ->where('resource', 'pengaduans')
                ->where('records.data.0.title', 'CCTV mati'));
    }

    public function test_admin_can_create_update_and_delete_pengaduan()
    {
        $this->actingAs(User::factory()->create());

        $reporter = User::factory()->create([
            'name' => 'PIC Koperasi',
            'phone' => '081234567890',
        ]);
        $picHelpdesk = User::factory()->create();
        $newPicHelpdesk = User::factory()->create();
        $province = Province::create(['name' => 'Jawa Tengah', 'code' => '33']);
        $city = City::create(['name' => 'Semarang', 'code' => '74', 'full_code' => '33.74', 'province_id' => $province->id]);
        $koperasi = Koperasi::create([
            'name' => 'Koperasi Contoh',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'user_id' => $reporter->id,
        ]);
        $category = PengaduanCategory::create(['name' => 'Sarpras', 'slug' => 'sarpras']);
        $subCategory = PengaduanCategory::create(['parent_id' => $category->id, 'name' => 'CCTV', 'slug' => 'cctv']);

        $this->post(route('admin.pengaduans.store'), [
            'reporter_user_id' => $reporter->id,
            'reporter_name' => $reporter->name,
            'reporter_phone' => $reporter->phone,
            'koperasi_id' => $koperasi->id,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'priority' => 'tinggi',
            'title' => 'CCTV mati',
            'detail' => 'CCTV tidak menyala.',
            'pic_helpdesk_id' => $picHelpdesk->id,
            'status' => 'ditugaskan',
            'sla_days' => 2,
            'progress_percentage' => 25,
            'resolution' => 'Menunggu teknisi.',
        ])->assertRedirect();

        $pengaduan = Pengaduan::where('title', 'CCTV mati')->firstOrFail();

        $this->assertSame('tinggi', $pengaduan->priority);
        $this->assertSame($subCategory->id, $pengaduan->sub_category_id);
        $this->assertNotNull($pengaduan->assigned_at);

        Carbon::setTestNow(now()->addHour()->startOfSecond());

        $this->put(route('admin.pengaduans.update', $pengaduan), [
            'reporter_user_id' => null,
            'reporter_name' => $reporter->name,
            'reporter_phone' => $reporter->phone,
            'koperasi_id' => null,
            'province_id' => null,
            'city_id' => null,
            'category_id' => null,
            'sub_category_id' => null,
            'pic_helpdesk_id' => $newPicHelpdesk->id,
            'priority' => 'normal',
            'title' => 'CCTV mati diperbarui',
            'detail' => 'Kabel adaptor rusak.',
            'status' => 'ditutup',
            'progress_percentage' => 100,
            'resolution' => 'Adaptor diganti.',
        ])->assertRedirect();

        $pengaduan->refresh();

        $this->assertNull($pengaduan->category_id);
        $this->assertNull($pengaduan->sub_category_id);
        $this->assertSame($newPicHelpdesk->id, $pengaduan->pic_helpdesk_id);
        $this->assertSame('ditutup', $pengaduan->status);
        $this->assertTrue($pengaduan->assigned_at->is(Carbon::getTestNow()));
        $this->assertTrue($pengaduan->completed_at->is(Carbon::getTestNow()));
        $this->assertSame($reporter->id, $pengaduan->reporter_user_id);
        $this->assertSame($reporter->name, $pengaduan->reporter_name);
        $this->assertSame($koperasi->id, $pengaduan->koperasi_id);
        $this->assertSame($province->id, $pengaduan->province_id);
        $this->assertSame($city->id, $pengaduan->city_id);
        $this->assertSame('tinggi', $pengaduan->priority);
        $this->assertSame('CCTV mati', $pengaduan->title);
        $this->assertSame('CCTV tidak menyala.', $pengaduan->detail);
        $this->assertSame(25, $pengaduan->progress_percentage);
        $this->assertSame('Menunggu teknisi.', $pengaduan->resolution);

        Carbon::setTestNow();

        $this->delete(route('admin.pengaduans.destroy', $pengaduan))
            ->assertRedirect();

        $this->assertSoftDeleted($pengaduan);
    }
}
