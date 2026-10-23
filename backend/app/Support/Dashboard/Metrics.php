<?php

namespace App\Support\Dashboard;

use App\Enums\PageStatus;
use App\Enums\Role;
use App\Models\Enquiry;
use App\Models\Event;
use App\Models\FeaturedMember;
use App\Models\GalleryItem;
use App\Models\MemberOrganisation;
use App\Models\Page;
use App\Models\Post;
use App\Models\Seminar;
use App\Models\TrainingBatch;
use App\Models\TrainingProgramme;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Figures for the admin dashboards. Enquiries are grouped by what the website's buttons put in the subject:
 * "Membership registration", "Registration: …" (events, programmes, menu categories), "Training: …",
 * "Event: …", "Question about …" — anything else is a general enquiry.
 */
class Metrics
{
    public const KINDS = [
        'membership' => 'Membership',
        'registration' => 'Registrations',
        'training' => 'Training interest',
        'question' => 'Questions',
        'general' => 'General',
    ];

    public static function kindOf(?string $subject): string
    {
        $s = trim((string) $subject);

        return match (true) {
            str_starts_with($s, 'Membership registration') => 'membership',
            str_starts_with($s, 'Registration:'), str_starts_with($s, 'Event:') => 'registration',
            str_starts_with($s, 'Training:') => 'training',
            str_starts_with($s, 'Question about') => 'question',
            default => 'general',
        };
    }

    /** "Registration: Skill Development · 16 Nov – 11 Dec 2026 (SD-01)" → "Skill Development". */
    public static function topicOf(string $subject): string
    {
        $topic = preg_replace('/^(Registration|Event|Training):\s*/', '', trim($subject)) ?? $subject;
        $topic = preg_replace('/\s*\([^)]*\)\s*$/', '', $topic) ?? $topic;
        $parts = array_filter(explode(' · ', $topic), fn ($p) => ! preg_match('/\b(19|20)\d{2}\b/', $p));

        return trim(implode(' · ', $parts)) ?: $topic;
    }

    /** Enquiries of the last $days days, per day, split into registrations and the rest. */
    public static function enquiriesPerDay(int $days = 30): array
    {
        $from = now()->startOfDay()->subDays($days - 1);
        $rows = Enquiry::query()->where('created_at', '>=', $from)->get(['subject', 'created_at']);
        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i);
            $out[$day->toDateString()] = ['label' => $day->format('j M'), 'registrations' => 0, 'other' => 0];
        }
        foreach ($rows as $row) {
            $key = Carbon::parse($row->created_at)->toDateString();
            if (isset($out[$key])) {
                in_array(self::kindOf($row->subject), ['membership', 'registration', 'training'], true)
                    ? $out[$key]['registrations']++ : $out[$key]['other']++;
            }
        }

        return array_values($out);
    }

    /** @return array<string, int> kind => count (all time) */
    public static function enquiriesByKind(): array
    {
        $counts = array_fill_keys(array_keys(self::KINDS), 0);
        foreach (Enquiry::query()->pluck('subject') as $subject) {
            $counts[self::kindOf($subject)]++;
        }

        return $counts;
    }

    /** @return array<string, int> status label => count */
    public static function enquiriesByStatus(): array
    {
        $counts = Enquiry::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return collect(Enquiry::STATUSES)->mapWithKeys(fn ($label, $status) => [$label => (int) ($counts[$status] ?? 0)])->all();
    }

    /** Top programmes / events / categories people registered for. */
    public static function topRegistrations(int $limit = 6): Collection
    {
        return Enquiry::query()->where(fn ($q) => $q->where('subject', 'like', 'Registration:%')->orWhere('subject', 'like', 'Event:%')->orWhere('subject', 'like', 'Training:%'))
            ->pluck('subject')
            ->map(fn ($s) => self::topicOf((string) $s))
            ->countBy()->sortDesc()->take($limit);
    }

    /** Count now vs the same number of days before, for the trend arrow. */
    public static function trend(string $model, int $days = 30, ?callable $scope = null): array
    {
        $q = fn () => tap($model::query(), fn ($query) => $scope && $scope($query));
        $now = $q()->where('created_at', '>=', now()->subDays($days))->count();
        $before = $q()->whereBetween('created_at', [now()->subDays($days * 2), now()->subDays($days)])->count();

        return ['now' => $now, 'before' => $before, 'delta' => $before ? (int) round(($now - $before) / $before * 100) : ($now ? 100 : 0)];
    }

    /** Daily counts for a sparkline. @return list<int> */
    public static function spark(string $model, int $days = 14, ?callable $scope = null): array
    {
        $from = now()->startOfDay()->subDays($days - 1);
        $query = $model::query()->where('created_at', '>=', $from);
        $scope && $scope($query);
        $byDay = $query->get(['created_at'])->countBy(fn ($r) => Carbon::parse($r->created_at)->toDateString());

        return collect(range(0, $days - 1))->map(fn ($i) => (int) ($byDay[$from->copy()->addDays($i)->toDateString()] ?? 0))->all();
    }

    /** @return list<array{label: string, value: int, icon: string, route?: string}> what the website holds */
    public static function content(): array
    {
        $today = now()->toDateString();

        return [
            ['label' => 'Published pages', 'value' => Page::query()->where('status', PageStatus::Published)->count(), 'icon' => 'document', 'route' => 'pages'],
            ['label' => 'News & blogs', 'value' => Post::query()->where('is_active', true)->count(), 'icon' => 'megaphone', 'route' => 'news'],
            ['label' => 'Upcoming events', 'value' => Event::query()->active()->whereDate('starts_on', '>=', $today)->count(), 'icon' => 'calendar', 'route' => 'events'],
            ['label' => 'Training programmes', 'value' => TrainingProgramme::query()->where('is_active', true)->count(), 'icon' => 'academic-cap', 'route' => 'training-programmes'],
            ['label' => 'Upcoming batches', 'value' => TrainingBatch::query()->upcoming()->count(), 'icon' => 'clock', 'route' => 'training-batches'],
            ['label' => 'Seminars', 'value' => Seminar::query()->active()->count(), 'icon' => 'slides', 'route' => 'seminars'],
            ['label' => 'Corporate members', 'value' => MemberOrganisation::query()->kind('corporate')->active()->count(), 'icon' => 'briefcase', 'route' => 'corporate-members'],
            ['label' => 'Institutional members', 'value' => MemberOrganisation::query()->kind('institutional')->active()->count(), 'icon' => 'building', 'route' => 'institutional-members'],
            ['label' => 'Member directory', 'value' => FeaturedMember::query()->active()->count(), 'icon' => 'user-group', 'route' => 'member-directory'],
            ['label' => 'Gallery photos', 'value' => GalleryItem::query()->active()->count(), 'icon' => 'photo', 'route' => 'gallery'],
        ];
    }

    /** Next events and batches, soonest first. */
    public static function upcoming(int $limit = 6): Collection
    {
        $today = now()->toDateString();
        $events = Event::query()->active()->whereDate('starts_on', '>=', $today)->orderBy('starts_on')->limit($limit)->get()
            ->map(fn (Event $e) => ['date' => $e->starts_on, 'title' => $e->title, 'meta' => $e->location, 'kind' => 'Event', 'resource' => 'events', 'id' => $e->id]);
        $batches = TrainingBatch::query()->upcoming()->with('programme:id,title')->limit($limit)->get()
            ->map(fn (TrainingBatch $b) => ['date' => $b->starts_on, 'title' => $b->programme?->title ?? 'Training', 'meta' => trim($b->formatLabel().' · '.$b->location, ' ·'), 'kind' => 'Batch', 'resource' => 'training-batches', 'id' => $b->id]);

        return $events->concat($batches)->sortBy(fn ($r) => $r['date']->timestamp)->take($limit)->values();
    }

    public static function members(): array
    {
        return [
            'total' => User::query()->where('role', Role::User)->count(),
            'trend' => self::trend(User::class, 30, fn ($q) => $q->where('role', Role::User)),
            'spark' => self::spark(User::class, 14, fn ($q) => $q->where('role', Role::User)),
        ];
    }
}
