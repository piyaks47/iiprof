<?php

namespace Tests\Feature;

use App\Livewire\Admin\Permissions;
use App\Livewire\Admin\Resources\Edit;
use App\Livewire\Admin\Resources\Index;
use App\Models\HomeItem;
use App\Models\HomeSection;
use App\Models\MembershipFee;
use App\Models\Setting;
use App\Models\TrainingBatch;
use App\Models\TrainingProgramme;
use App\Models\User;
use App\Support\Access;
use App\Support\HomeBlocks;
use App\Support\MembershipTraining;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MembershipTrainingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-25 10:00:00');
        $this->seed(DatabaseSeeder::class);
    }

    private function sections(): \Illuminate\Support\Collection
    {
        return collect($this->getJson('/api/v1/home')->assertOk()->json('data.sections'))->keyBy('key');
    }

    public function test_membership_blocks_on_the_homepage(): void
    {
        $s = $this->sections();

        $hub = $s['membership_hub'];
        $this->assertSame('#register', $hub['button_url']);
        $this->assertStringContainsString('<strong>International Institute of Professionals</strong>', $hub['body_html']);
        $this->assertSame(['15', '₹250', 'Free'], array_column($hub['items'], 'value'));
        $this->assertSame([['key' => 'patrons', 'label' => 'Patrons']], $hub['extra']['member_categories']);
        $this->assertCount(5, $hub['extra']['members']);
        $this->assertSame('Mr. Chandan Roy', $hub['extra']['members'][0]['name']);

        $this->assertCount(7, $s['membership_objectives']['items']);

        $apply = $s['membership_apply'];
        $this->assertCount(4, $apply['items']);
        $this->assertCount(15, $apply['extra']['fees']);
        $member = collect($apply['extra']['fees'])->firstWhere('code', 'MIIP');
        $this->assertSame(['1500', '15000'], [$member['annual_fee'], $member['lifetime_fee']]);
        $this->assertNull(collect($apply['extra']['fees'])->firstWhere('code', 'STIIP')['lifetime_fee']);

        // The existing membership blocks are still there.
        $this->assertCount(6, $s['membership']['items']);
        $this->assertCount(9, $s['benefits']['items']);
    }

    public function test_training_blocks_on_the_homepage(): void
    {
        $s = $this->sections();
        $training = $s['training'];

        $this->assertCount(6, $training['items']);
        $this->assertSame(['skill-development', 'entrepreneurship-development'], array_slice(array_column($training['items'], 'slug'), 0, 2), 'featured first');
        $this->assertSame('/training/skill-development', $training['items'][0]['path']);
        $this->assertSame('SD-01', $training['items'][0]['next_batch']['batch_code']);
        $this->assertSame(['open', 'in-house', 'online'], array_column($training['extra']['types'], 'key'));

        $dates = array_column($training['extra']['batches'], 'starts_on');
        $sorted = $dates;
        sort($sorted);
        $this->assertSame($sorted, $dates, 'soonest first');
        $this->assertSame('16 Nov – 11 Dec 2026', $training['extra']['batches'][0]['dates']);
        $this->assertSame('/training/skill-development', $training['extra']['batches'][0]['programme']['path']);

        $this->assertCount(3, $s['training_formats']['items']);
        $this->assertCount(6, $s['training_benefits']['items']);
        $this->assertCount(6, $s['programmes']['items'], 'the programmes & initiatives cards are kept');

        // Faculty appears once a trainer is added.
        $this->assertArrayNotHasKey('faculty', $s->all());
        HomeItem::query()->create(['section' => 'faculty', 'title' => 'A. Trainer', 'subtitle' => 'Soft-skills coach']);
        $this->assertSame('A. Trainer', $this->sections()['faculty']['items'][0]['title']);
    }

    public function test_training_pages_and_finished_batches(): void
    {
        $this->getJson('/api/v1/training')->assertOk()
            ->assertJsonCount(6, 'data.programmes')
            ->assertJsonCount(6, 'data.batches');

        $detail = $this->getJson('/api/v1/training/skill-development')->assertOk()->json('data');
        $this->assertSame('Skill Development & Vocational Training', $detail['title']);
        $this->assertContains('Digital literacy', $detail['modules']);
        $this->assertStringContainsString('<li>School and college leavers</li>', $detail['audience_html']);
        $this->assertCount(2, $detail['batches']);
        $this->assertSame('Open programmes', $detail['type_label']);

        // Finished batches drop off by themselves; switched-off programmes disappear.
        $this->travelTo('2026-12-20');
        $this->assertCount(1, $this->getJson('/api/v1/training/skill-development')->json('data.batches'));
        TrainingProgramme::query()->where('slug', 'interior-design')->update(['is_active' => false]);
        $this->getJson('/api/v1/training/interior-design')->assertNotFound();
        $this->assertNotContains('Interior Design', array_column($this->getJson('/api/v1/training')->json('data.batches.*.programme'), 'title'));
    }

    public function test_upgrade_places_the_blocks_and_keeps_a_custom_order(): void
    {
        $reset = function (array $order) {
            HomeItem::query()->whereIn('section', MembershipTraining::NEW_KEYS)->delete();
            HomeSection::query()->whereIn('key', MembershipTraining::NEW_KEYS)->delete();
            Setting::query()->where('key', MembershipTraining::MARKER)->delete();
            foreach ($order as $i => $key) {
                HomeSection::query()->where('key', $key)->update(['sort_order' => $i + 1]);
            }
        };

        // Previous version, untouched order → the full new order.
        $reset(HomeBlocks::DEFAULT_ORDER);
        MembershipTraining::seedOnce();
        $this->assertSame(MembershipTraining::DEFAULT_ORDER, HomeSection::query()->ordered()->pluck('key')->all());

        // Custom order (contact first) → kept, new blocks next to their anchors.
        $reset(['contact', ...array_diff(HomeBlocks::DEFAULT_ORDER, ['contact'])]);
        MembershipTraining::seedOnce();
        $keys = HomeSection::query()->ordered()->pluck('key')->all();
        $this->assertSame('contact', $keys[0]);
        $this->assertSame('membership_hub', $keys[array_search('about', $keys, true) + 1]);
        $this->assertSame('membership_apply', $keys[array_search('membership', $keys, true) + 1]);
        $this->assertSame(['training', 'training_formats', 'training_benefits', 'faculty', 'programmes'], array_slice($keys, array_search('training', $keys, true), 5));
        $this->assertSame(15, MembershipFee::query()->count(), 'existing fees are not added twice');
    }

    public function test_admin_screens_and_role_based_access(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $member = User::factory()->create();

        // Sidebar groups and default access.
        $this->actingAs($admin)->get('/admin')->assertSee('Membership fees')->assertSee('Training batches')->assertSee('Member directory');
        $this->actingAs($editor)->get('/admin/manage/training-programmes')->assertOk()->assertSee('Skill Development');
        $this->actingAs($editor)->get('/admin/manage/membership-fees')->assertOk()->assertSee('Super Senior Citizen Member');
        $this->actingAs($member)->get('/admin')->assertDontSee('Membership fees')->assertDontSee('Training batches');
        $this->actingAs($member)->get('/admin/manage/training-batches')->assertForbidden();

        // Membership and training sections: only their own blocks, editable by their managers.
        $this->actingAs($editor)->get('/admin/manage/membership-sections')->assertOk()->assertSee('Membership — how to apply &amp; fees', false)->assertDontSee('Hero slider');
        $this->actingAs($editor)->get('/admin/manage/training-sections')->assertOk()->assertSee('Training — types of training')->assertDontSee('Membership — why join');
        $hero = HomeSection::query()->where('key', 'hero')->firstOrFail();
        $this->actingAs($editor)->get("/admin/manage/training-sections/{$hero->id}/edit")->assertNotFound();

        // An admin gives Users read access to Training and takes Membership away from Editors.
        $this->actingAs($admin);
        Livewire::test(Permissions::class)
            ->assertSet('matrix.editor.membership', '2')
            ->set('matrix.editor.membership', '0')
            ->set('matrix.user.training', '1')
            ->call('save')->assertHasNoErrors();

        $this->assertSame(Access::NONE, Access::level($editor, 'membership'));
        $this->actingAs($editor)->get('/admin/manage/membership-fees')->assertForbidden();
        $this->actingAs($editor)->get('/admin/manage/member-directory')->assertForbidden();
        $this->actingAs($editor)->get('/admin')->assertDontSee('Membership fees')->assertSee('Training batches');

        $this->actingAs($member)->get('/admin/manage/training-batches')->assertOk()->assertSee('View only');
        $this->actingAs($member)->get('/admin/manage/training-batches/create')->assertForbidden();
        $batch = TrainingBatch::query()->firstOrFail();
        Livewire::actingAs($member)->test(Index::class, ['resource' => 'training-batches'])->call('toggle', $batch->id)->assertForbidden();
    }

    public function test_crud_screens_for_programmes_and_batches(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Edit::class, ['resource' => 'training-programmes'])
            ->set('data.title', 'Digital Marketing Basics')
            ->set('data.type', 'online')
            ->set('data.modules', "SEO\nSocial media\n\nEmail")
            ->call('save')->assertHasNoErrors();
        $programme = TrainingProgramme::query()->where('title', 'Digital Marketing Basics')->firstOrFail();
        $this->assertSame('digital-marketing-basics', $programme->slug);
        $this->assertSame(['SEO', 'Social media', 'Email'], $this->getJson('/api/v1/training/digital-marketing-basics')->json('data.modules'));

        Livewire::test(Edit::class, ['resource' => 'training-batches'])
            ->set('data.training_programme_id', (string) $programme->id)
            ->set('data.format', 'online')
            ->set('data.starts_on', '2026-12-10')
            ->set('data.ends_on', '2026-12-01')
            ->call('save')
            ->assertHasErrors(['data.ends_on']);

        Livewire::test(Edit::class, ['resource' => 'training-batches'])
            ->set('data.training_programme_id', (string) $programme->id)
            ->set('data.format', 'online')
            ->set('data.starts_on', '2026-12-10')
            ->set('data.ends_on', '2026-12-12')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('10–12 Dec 2026', $this->getJson('/api/v1/training/digital-marketing-basics')->json('data.batches.0.dates'));

        // Items of one block cannot be opened through another block's screen.
        $step = HomeItem::query()->in('membership_apply')->firstOrFail();
        $this->get("/admin/manage/membership-steps/{$step->id}/edit")->assertOk();
        $this->get("/admin/manage/training-formats/{$step->id}/edit")->assertNotFound();
    }
}
