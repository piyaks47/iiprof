<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A block on the homepage. The set of sections is fixed (one row per key below);
 * admins change their texts, order and visibility, and manage their items separately.
 */
class HomeSection extends Model
{
    use HasImages, Orderable;

    /**
     * key => name, what it shows, which text fields it uses, the admin resource holding its items, and the access
     * area whose managers may also edit it (besides Homepage sections; default home-sections).
     *
     * @var array<string, array{name: string, description: string, uses: list<string>, items: ?string, area?: string}>
     */
    public const SECTIONS = [
        'hero' => [
            'name' => 'Hero slider',
            'description' => 'Full-width slider at the top of the page. Each slide has its own text, image and button; the button set here is shown on every slide.',
            'uses' => ['button_text', 'button_url'],
            'items' => 'hero-slides',
        ],
        'stats' => [
            'name' => 'Key figures',
            'description' => 'A band of animated numbers under the slider.',
            'uses' => ['eyebrow', 'title'],
            'items' => 'stats',
        ],
        'about' => [
            'name' => 'About us',
            'description' => 'Introduction with a two-tone heading, text, image and a call-to-action button.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'body', 'button_text', 'button_url', 'image'],
            'items' => null,
        ],
        'highlights' => [
            'name' => 'Highlights',
            'description' => 'Dark band with the key promises of the institute.',
            'uses' => ['eyebrow', 'title', 'subtitle', 'image'],
            'items' => 'highlights',
        ],
        'events' => [
            'name' => 'Events',
            'description' => 'Carousel of events. Each event gets its own page.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'button_text', 'button_url'],
            'items' => 'events',
        ],
        'gallery' => [
            'name' => 'Gallery',
            'description' => 'Photo grid with a full-screen viewer. Hidden while it has no photos.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle'],
            'items' => 'gallery',
        ],
        'pillars' => [
            'name' => 'Feature tiles',
            'description' => 'Large photo tiles, each linking to one area of IIP (Hunar, Jeevika, Khazana …).',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle'],
            'items' => 'feature-tiles',
        ],
        'impact' => [
            'name' => 'Impact numbers',
            'description' => 'Big numbers with a short explanation, on a dark band with an optional photo.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'image'],
            'items' => 'impact-figures',
        ],
        'benefits' => [
            'name' => 'Membership benefits',
            'description' => 'Icon grid of the facilities members get, with a button.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'button_text', 'button_url'],
            'items' => 'member-benefits',
            'area' => 'membership',
        ],
        'membership' => [
            'name' => 'Membership categories',
            'description' => 'Cards for the membership grades, each with its own button.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'button_text', 'button_url'],
            'items' => 'membership-categories',
            'area' => 'membership',
        ],
        'programmes' => [
            'name' => 'Programmes',
            'description' => 'Photo cards for courses, admissions and initiatives.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'button_text', 'button_url'],
            'items' => 'programmes',
            'area' => 'training',
        ],
        'membership_hub' => [
            'name' => 'Membership — join & members',
            'description' => 'Three panels: join IIP (text, photo, button), member community highlights, and the member directory in tabs.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'body', 'image', 'button_text', 'button_url'],
            'items' => 'member-highlights',
            'area' => 'membership',
        ],
        'membership_objectives' => [
            'name' => 'Membership — why join',
            'description' => 'An introduction to membership next to its numbered objectives.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'body', 'image'],
            'items' => 'membership-objectives',
            'area' => 'membership',
        ],
        'membership_apply' => [
            'name' => 'Membership — how to apply & fees',
            'description' => 'The steps to become a member next to the fee table (Membership fees), with a button.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'body', 'button_text', 'button_url'],
            'items' => 'membership-steps',
            'area' => 'membership',
        ],
        'training' => [
            'name' => 'Training & development',
            'description' => 'Programme cards in tabs (open, in-company, online) next to the scheduled dates of the next batches.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'button_text', 'button_url'],
            'items' => 'training-programmes',
            'area' => 'training',
        ],
        'training_formats' => [
            'name' => 'Training — types of training',
            'description' => 'How training is delivered: open batches, in-company and online.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle'],
            'items' => 'training-formats',
            'area' => 'training',
        ],
        'training_benefits' => [
            'name' => 'Training — why train with IIP',
            'description' => 'Dark band with the benefits of training at IIP, an optional photo and a button.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'image', 'button_text', 'button_url'],
            'items' => 'training-benefits',
            'area' => 'training',
        ],
        'faculty' => [
            'name' => 'Training — trainers & faculty',
            'description' => 'Photos and short profiles of the trainers. Hidden until you add one.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle'],
            'items' => 'faculty',
            'area' => 'training',
        ],
        'next_event' => [
            'name' => 'Next event',
            'description' => 'The next upcoming event with a date tag, a live countdown and its button. Hidden when no event is coming up.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'button_text', 'button_url'],
            'items' => 'events',
            'area' => 'events',
        ],
        'past_events' => [
            'name' => 'Past events',
            'description' => 'Carousel of events that have taken place, with their summary and a photos / video link.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'button_text', 'button_url'],
            'items' => 'events',
            'area' => 'events',
        ],
        'news' => [
            'name' => 'News & blogs',
            'description' => 'Gallery of the latest news, blog posts and articles (the featured one large).',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'button_text', 'button_url'],
            'items' => 'news',
            'area' => 'news',
        ],
        'seminars' => [
            'name' => 'Seminars',
            'description' => 'Full-width seminar banners (image, title, button to the destination link).',
            'uses' => ['eyebrow', 'title', 'title_accent'],
            'items' => 'seminars',
            'area' => 'seminars',
        ],
        'board' => [
            'name' => 'Board members',
            'description' => 'The people of the management bodies (Website → Management & team) in tabs, with their profiles.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'button_text', 'button_url'],
            'items' => 'team',
            'area' => 'team',
        ],
        'corporate_members' => [
            'name' => 'Corporate members',
            'description' => 'Logo wall of corporate members, filterable by industry.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'button_text', 'button_url'],
            'items' => 'corporate-members',
            'area' => 'corporate-members',
        ],
        'institutions' => [
            'name' => 'Institutional members',
            'description' => 'Universities, colleges, schools and institutes with their logos, by type of institution.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'button_text', 'button_url'],
            'items' => 'institutional-members',
            'area' => 'institutional-members',
        ],
        'testimonials' => [
            'name' => 'Testimonials',
            'description' => 'Member quotes with photos and videos (the testimonials themselves are under Website → Testimonials).',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'button_text', 'button_url'],
            'items' => 'testimonials',
        ],
        'partners' => [
            'name' => 'Registrations & partners',
            'description' => 'A strip of logos or names: registrations, affiliations and partners.',
            'uses' => ['eyebrow', 'title', 'subtitle'],
            'items' => 'partners',
        ],
        'faq' => [
            'name' => 'FAQ',
            'description' => 'Questions and answers in an accordion, with a button.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'button_text', 'button_url'],
            'items' => 'faqs',
        ],
        'join' => [
            'name' => 'Join banner',
            'description' => 'Full-width call to action on a photo, with a button.',
            'uses' => ['eyebrow', 'title', 'title_accent', 'subtitle', 'button_text', 'button_url', 'image'],
            'items' => null,
        ],
        'contact' => [
            'name' => 'Contact & quote',
            'description' => 'Office address (from Settings) on a photo card, a quote and the follow-us links.',
            'uses' => ['eyebrow', 'title', 'subtitle', 'body', 'button_text', 'button_url', 'image'],
            'items' => null,
        ],
    ];

    protected $fillable = [
        'key', 'name', 'eyebrow', 'title', 'title_accent', 'subtitle', 'body',
        'button_text', 'button_url', 'image', 'sort_order', 'is_active',
    ];

    public function definition(): array
    {
        return self::SECTIONS[$this->key] ?? ['name' => $this->name, 'description' => '', 'uses' => [], 'items' => null];
    }

    /** Access area of the block (membership, training … or home-sections). */
    public function area(): string
    {
        return $this->definition()['area'] ?? 'home-sections';
    }

    /** @return list<string> keys of the blocks belonging to an access area */
    public static function keysIn(string $area): array
    {
        return array_keys(array_filter(self::SECTIONS, fn (array $d) => ($d['area'] ?? 'home-sections') === $area));
    }

    public function uses(string $field): bool
    {
        return in_array($field, $this->definition()['uses'], true);
    }

    public function bodyHtml(): string
    {
        return $this->body ? (string) Str::markdown($this->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]) : '';
    }

    /** Make sure a row exists for every known section (new sections appear after an update). */
    public static function ensureAll(): void
    {
        if (static::query()->whereIn('key', array_keys(self::SECTIONS))->count() === count(self::SECTIONS)) {
            return;
        }

        $order = (int) static::query()->max('sort_order') + 1;
        foreach (self::SECTIONS as $key => $definition) {
            if (static::query()->firstOrCreate(['key' => $key], ['name' => $definition['name'], 'sort_order' => $order])->wasRecentlyCreated) {
                $order++;
            }
        }
    }
}
