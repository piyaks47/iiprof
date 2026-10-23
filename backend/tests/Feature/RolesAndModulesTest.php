<?php

namespace Tests\Feature;

use App\Livewire\Admin\DashboardImagePicker;
use App\Livewire\Admin\Modules\Edit as ModuleEdit;
use App\Livewire\Admin\UserTypes\Edit as UserTypeEdit;
use App\Livewire\Admin\Users\Edit as UserEdit;
use App\Models\Module;
use App\Models\User;
use App\Models\UserSubtype;
use App\Models\UserType;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RolesAndModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([UserTypeSeeder::class, ModuleSeeder::class]);
    }

    private function type(string $name): UserType
    {
        return UserType::query()->where('name', $name)->firstOrFail();
    }

    public function test_seeders_create_all_user_types_with_menu_subtypes(): void
    {
        $this->assertSame(18, UserType::query()->count());
        $this->assertSame(
            ['Banks/NBFC', 'Microfinance', 'Pvt Equity', 'Venture Capital', 'FDI', 'CSR', 'Grants'],
            $this->type('Lenders')->subtypes->pluck('name')->all(),
        );
        $this->assertTrue(Module::query()->where('key', 'khazana')->first()->userTypes->contains('name', 'Lenders'));
    }

    public function test_each_role_gets_its_own_dashboard(): void
    {
        // Compact dashboards: no greeting banner; the dashboard image stays as a thumbnail linking to the profile.
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk()
            ->assertSee('Enquiries &amp; registrations', false)->assertSee('Members by type')->assertSee('Coming up')
            ->assertSee('Live ·')->assertDontSee('Good morning')->assertDontSee('Good afternoon')->assertDontSee('Good evening')
            ->assertSee('#dashboard-image', false);
        $this->actingAs(User::factory()->editor()->create())->get('/admin')->assertOk()->assertSee('Keep the website content fresh')
            ->assertSee('Website content')->assertDontSee('Members by type');
        $this->actingAs(User::factory()->create(['user_type_id' => $this->type('Lenders')->id]))
            ->get('/admin')->assertOk()->assertSee('Your modules')->assertSee('Khazana')->assertDontSee('Members by type')
            ->assertDontSee('Enquiries &amp; registrations', false);
    }

    public function test_modules_follow_role_and_user_type(): void
    {
        $lender = User::factory()->create(['user_type_id' => $this->type('Lenders')->id]);
        $doctor = User::factory()->create(['user_type_id' => $this->type('Doctor')->id]);
        $nobody = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->assertEqualsCanonicalizing(['community', 'khazana', 'philanthropy'], $lender->availableModules()->pluck('key')->all());
        $this->assertContains('health', $doctor->availableModules()->pluck('key')->all());
        $this->assertSame(['community'], $nobody->availableModules()->pluck('key')->all());
        $this->assertCount(Module::query()->count(), $admin->availableModules());

        $this->actingAs($lender)->get('/admin/m/khazana')->assertOk()->assertSee('Banks/NBFC');
        $this->actingAs($lender)->get('/admin/m/health')->assertForbidden();
        $this->actingAs($doctor)->get('/admin/m/health')->assertOk();
    }

    public function test_switched_off_module_or_inactive_type_removes_access(): void
    {
        $lender = User::factory()->create(['user_type_id' => $this->type('Lenders')->id]);

        Module::query()->where('key', 'khazana')->update(['is_active' => false]);
        $this->actingAs($lender)->get('/admin/m/khazana')->assertNotFound();

        Module::query()->where('key', 'khazana')->update(['is_active' => true]);
        $this->type('Lenders')->update(['is_active' => false]);
        $this->actingAs($lender->fresh())->get('/admin/m/khazana')->assertForbidden();
    }

    public function test_members_cannot_open_pages_or_admin_areas(): void
    {
        $member = User::factory()->create();

        foreach (['/admin/pages', '/admin/users', '/admin/user-types', '/admin/modules', '/admin/settings'] as $url) {
            $this->actingAs($member)->get($url)->assertForbidden();
        }
        $this->actingAs($member)->get('/admin/profile')->assertOk();
    }

    public function test_admin_creates_user_type_with_subtypes_and_modules(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $module = Module::query()->where('key', 'jeevika')->first();

        Livewire::test(UserTypeEdit::class)
            ->set('name', 'Mentors')
            ->set('icon', 'academic-cap')
            ->set('initialSubtypes', "Career\nStartup\n\nCareer")
            ->set('moduleIds', [(string) $module->id])
            ->call('save')
            ->assertHasNoErrors();

        $type = $this->type('Mentors');
        $this->assertSame(['Career', 'Startup'], $type->subtypes->pluck('name')->all());
        $this->assertTrue($type->modules->contains($module));
    }

    public function test_subtype_inline_crud(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $type = $this->type('Fund Managers');
        $first = $type->subtypes->first();

        $component = Livewire::test(UserTypeEdit::class, ['userType' => $type])
            ->set('newSubtype', 'Wealth Manager')->call('addSubtype')->assertHasNoErrors()
            ->set('newSubtype', 'DSA')->call('addSubtype')->assertHasErrors('newSubtype')
            ->set("subtypeRows.{$first->id}.name", 'Portfolio Management Service')
            ->call('moveSubtype', $first->id, 'down');

        $this->assertDatabaseHas('user_subtypes', ['user_type_id' => $type->id, 'name' => 'Wealth Manager']);
        $this->assertSame('Portfolio Management Service', $first->fresh()->name);
        $this->assertSame('DSA', $type->subtypes()->first()->name);

        $component->call('deleteSubtype', $first->id);
        $this->assertModelMissing($first);
    }

    public function test_admin_assigns_type_and_subtype_to_user(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $member = User::factory()->create();
        $lenders = $this->type('Lenders');
        $nbfc = $lenders->subtypes->first();
        $wrong = $this->type('Borrowers')->subtypes->first();

        Livewire::test(UserEdit::class, ['user' => $member])
            ->set('form.user_type_id', (string) $lenders->id)
            ->set('form.user_subtype_id', (string) $wrong->id)
            ->call('save')->assertHasErrors('form.user_subtype_id')
            ->set('form.user_subtype_id', (string) $nbfc->id)
            ->call('save')->assertHasNoErrors();

        $this->assertSame('Lenders · Banks/NBFC', $member->fresh()->typeLabel());
    }

    public function test_module_roles_and_types_can_be_edited(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $module = Module::query()->where('key', 'edu10')->first();

        Livewire::test(ModuleEdit::class, ['module' => $module])
            ->set('roles', ['user'])
            ->set('typeIds', [])
            ->call('save')->assertHasNoErrors();

        $this->assertContains('edu10', User::factory()->create()->availableModules()->pluck('key')->all());
    }

    public function test_dashboard_image_preset_upload_and_fallbacks(): void
    {
        Storage::fake('public');
        $lenders = $this->type('Lenders');
        $member = User::factory()->create(['user_type_id' => $lenders->id]);
        $this->actingAs($member);

        $this->assertStringStartsWith('data:image/svg+xml', $member->dashboardImageUrl()); // role default preset

        Livewire::test(DashboardImagePicker::class, ['model' => $member])->call('choose', 'preset:forest');
        $this->assertSame('preset:forest', $member->fresh()->dashboard_image);

        Livewire::test(DashboardImagePicker::class, ['model' => $member])
            ->set('upload', UploadedFile::fake()->image('banner.jpg', 1600, 480));
        $path = $member->fresh()->dashboard_image;
        Storage::disk('public')->assertExists($path);

        Livewire::test(DashboardImagePicker::class, ['model' => $member])->call('useDefault');
        Storage::disk('public')->assertMissing($path);
        $this->assertNull($member->fresh()->dashboard_image);

        // Members may not change another user's image or a user type's default.
        Livewire::test(DashboardImagePicker::class, ['model' => $lenders])->assertForbidden();
        Livewire::test(DashboardImagePicker::class, ['model' => User::factory()->create()])->assertForbidden();
    }

    public function test_user_types_api(): void
    {
        $this->getJson('/api/v1/user-types')
            ->assertOk()
            ->assertJsonCount(18, 'data')
            ->assertJsonPath('data.0.name', 'Management')
            ->assertJsonPath('data.0.subtypes.0.name', 'Director Council');
    }
}
