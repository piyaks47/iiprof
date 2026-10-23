<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\FeaturedMember;
use App\Models\MemberOrganisation;
use App\Models\MembershipFee;
use App\Models\Post;
use App\Models\Seminar;
use App\Models\TeamMember;
use App\Models\TrainingBatch;
use App\Models\TrainingProgramme;
use App\Models\GalleryItem;
use App\Models\HeroSlide;
use App\Models\Highlight;
use App\Models\HomeItem;
use App\Models\HomeSection;
use App\Models\Stat;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    /** Homepage: the active sections in order, each with its items. Sections without items are left out. */
    public function __invoke(): JsonResponse
    {
        HomeSection::ensureAll();

        $sections = HomeSection::query()->active()->ordered()
            ->whereIn('key', array_keys(HomeSection::SECTIONS))
            ->get()
            ->map(function (HomeSection $section) {
                $items = $this->items($section->key);
                $extra = $this->extra($section->key);

                $empty = match ($section->key) {
                    'membership_hub' => $items === [] && $extra['members'] === [] && blank($section->body),
                    'membership_apply' => $items === [] && $extra['fees'] === [],
                    default => $items !== null && $items === [],
                };
                if ($empty) {
                    return null; // e.g. a gallery without photos
                }

                return [
                    'key' => $section->key,
                    'eyebrow' => $section->eyebrow,
                    'title' => $section->title,
                    'title_accent' => $section->title_accent,
                    'subtitle' => $section->subtitle,
                    'body' => $section->body,
                    'body_html' => $section->bodyHtml(),
                    'button_text' => $section->button_text,
                    'button_url' => $section->button_url,
                    'image_url' => $section->imageUrl(),
                    'items' => $items ?? [],
                    'extra' => $extra,
                ];
            })
            ->filter()
            ->values();

        return response()->json(['data' => ['sections' => $sections]]);
    }

    /** @return list<array<string, mixed>>|null null when the section has no items of its own */
    private function items(string $key): ?array
    {
        return match ($key) {
            'hero' => HeroSlide::query()->active()->ordered()->get()->map(fn (HeroSlide $slide) => [
                'id' => $slide->id,
                'eyebrow' => $slide->eyebrow,
                'title' => $slide->title,
                'subtitle' => $slide->subtitle,
                'image_url' => $slide->imageUrl(),
                'button_text' => $slide->button_text,
                'button_url' => $slide->button_url,
            ])->all(),
            'stats' => Stat::query()->active()->ordered()->get(['id', 'value', 'label'])->toArray(),
            'highlights' => Highlight::query()->active()->ordered()->get(['id', 'title', 'description', 'icon'])->toArray(),
            // What's on: upcoming and undated events (past ones have their own block).
            'events' => Event::query()->active()->where(fn ($q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '>=', now()->toDateString()))
                ->ordered()->limit(12)->get()->map(fn (Event $event) => EventController::summary($event))->all(),
            'next_event' => Event::query()->active()->whereDate('starts_on', '>=', now()->toDateString())->orderBy('starts_on')->orderBy('id')
                ->limit(1)->get()->map(fn (Event $event) => EventController::summary($event) + ['description_html' => $event->descriptionHtml()])->all(),
            'past_events' => Event::query()->active()->whereDate('starts_on', '<', now()->toDateString())->orderByDesc('starts_on')->orderByDesc('id')
                ->limit(10)->get()->map(fn (Event $event) => EventController::summary($event))->all(),
            'news' => Post::query()->visible()->limit(12)->get()->sortByDesc('is_featured')->take(7)->values()
                ->map(fn (Post $post) => $post->toSummary())->all(),
            'seminars' => Seminar::query()->active()->ordered()->get()->map(fn (Seminar $s) => $s->toSummary())->all(),
            'board' => TeamMember::query()->active()->ordered()->get()
                ->sortBy(fn (TeamMember $m) => array_search($m->group, array_keys(TeamMember::GROUPS), true))->values()
                ->map(fn (TeamMember $m) => [
                    'id' => $m->id, 'group' => $m->group, 'name' => $m->name, 'role' => $m->role, 'bio' => $m->bio,
                    'bio_html' => $m->bio ? (string) \Illuminate\Support\Str::markdown($m->bio, ['html_input' => 'strip', 'allow_unsafe_links' => false]) : '',
                    'photo_url' => $m->imageUrl('photo'),
                ])->all(),
            'corporate_members', 'institutions' => MemberOrganisation::query()->kind($key === 'institutions' ? 'institutional' : 'corporate')->active()
                ->orderByDesc('is_featured')->ordered()->get()->map(fn (MemberOrganisation $o) => $o->toApi())->all(),
            'gallery' => GalleryItem::query()->active()->ordered()->get()->map(fn (GalleryItem $item) => [
                'id' => $item->id, 'title' => $item->title, 'image_url' => $item->imageUrl(),
            ])->all(),
            'testimonials' => Testimonial::query()->active()->ordered()->limit(9)->get()
                ->map(fn (Testimonial $t) => PeopleController::testimonial($t))->all(),
            'pillars', 'impact', 'benefits', 'membership', 'programmes', 'partners', 'faq',
            'membership_hub', 'membership_objectives', 'membership_apply', 'training_formats', 'training_benefits', 'faculty' => HomeItem::query()->in($key)->active()->ordered()->get()
                ->map(fn (HomeItem $item) => $item->toApi())->all(),
            'training' => TrainingController::programmes()->all(),
            default => null,
        };
    }

    /**
     * Data a block needs besides its items (member directory, fee table, scheduled batches).
     *
     * @return array<string, mixed>|null
     */
    private function extra(string $key): ?array
    {
        return match ($key) {
            'membership_hub' => (function () {
                $members = FeaturedMember::query()->active()->orderBy('category')->ordered()->get();
                $categories = collect(FeaturedMember::CATEGORIES)
                    ->filter(fn ($label, $category) => $members->contains('category', $category))
                    ->map(fn ($label, $category) => ['key' => $category, 'label' => $label])->values()->all();

                return ['members' => $members->map(fn (FeaturedMember $m) => $m->toApi())->values()->all(), 'member_categories' => $categories];
            })(),
            'membership_apply' => ['fees' => MembershipFee::query()->active()->ordered()->get()->map(fn (MembershipFee $f) => $f->toApi())->all()],
            'board' => ['groups' => collect(TeamMember::GROUPS)
                ->filter(fn ($title, $group) => TeamMember::query()->active()->where('group', $group)->exists())
                ->map(fn ($title, $group) => ['key' => $group, 'title' => $title, 'path' => '/management/'.$group])->values()->all()],
            'training' => [
                'batches' => TrainingBatch::query()->upcoming()->with('programme')->whereHas('programme', fn ($q) => $q->where('is_active', true))
                    ->limit(8)->get()->map(fn (TrainingBatch $b) => $b->toApi())->all(),
                'types' => collect(TrainingProgramme::TYPES)->map(fn ($label, $type) => ['key' => $type, 'label' => $label])->values()->all(),
            ],
            default => null,
        };
    }
}
