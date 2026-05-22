<?php

namespace Tests\Feature\Admin;

use App\Models\PengaduanCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PengaduanCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_pengaduan_categories()
    {
        $this->actingAs(User::factory()->create());

        PengaduanCategory::create([
            'name' => 'Kendala Sarpras',
            'slug' => 'kendala-sarpras',
        ]);

        $this->get(route('admin.pengaduan-categories.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/index')
                ->where('resource', 'pengaduanCategories')
                ->where('records.data.0.name', 'Kendala Sarpras'));
    }

    public function test_admin_can_create_update_and_delete_pengaduan_category()
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.pengaduan-categories.store'), [
            'name' => 'Akun dan Login',
            'description' => 'Bantuan akun pengguna.',
            'is_active' => true,
        ])->assertRedirect();

        $category = PengaduanCategory::where('slug', 'akun-dan-login')->firstOrFail();

        $this->assertSame('Akun dan Login', $category->name);
        $this->assertTrue($category->is_active);

        $this->put(route('admin.pengaduan-categories.update', $category), [
            'name' => 'Akun Helpdesk',
            'slug' => 'akun-helpdesk',
            'description' => 'Bantuan akun helpdesk.',
            'is_active' => false,
        ])->assertRedirect();

        $category->refresh();

        $this->assertSame('Akun Helpdesk', $category->name);
        $this->assertSame('akun-helpdesk', $category->slug);
        $this->assertFalse($category->is_active);

        $this->delete(route('admin.pengaduan-categories.destroy', $category))
            ->assertRedirect();

        $this->assertModelMissing($category);
    }
}
