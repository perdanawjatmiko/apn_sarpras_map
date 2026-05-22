<?php

namespace Tests\Feature\Admin;

use App\Models\City;
use App\Models\Koperasi;
use App\Models\Pengaduan;
use App\Models\PengaduanCategory;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->put(route('admin.pengaduans.update', $pengaduan), [
            'reporter_name' => $reporter->name,
            'reporter_phone' => $reporter->phone,
            'priority' => 'normal',
            'title' => 'CCTV mati diperbarui',
            'detail' => 'Kabel adaptor rusak.',
            'status' => 'selesai',
            'progress_percentage' => 100,
            'resolution' => 'Adaptor diganti.',
        ])->assertRedirect();

        $pengaduan->refresh();

        $this->assertSame('CCTV mati diperbarui', $pengaduan->title);
        $this->assertSame('selesai', $pengaduan->status);
        $this->assertSame(100, $pengaduan->progress_percentage);

        $this->delete(route('admin.pengaduans.destroy', $pengaduan))
            ->assertRedirect();

        $this->assertSoftDeleted($pengaduan);
    }
}
