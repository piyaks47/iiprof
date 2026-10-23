<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Sample events so the events rail, the events page and the event detail pages have something to show.
 * They are placeholders: edit or delete them under Website → Events. Added once (marker setting
 * `demo_events`), so deleting them in the admin is permanent.
 */
class DemoContent
{
    public const MARKER = 'demo_events';

    /** Covers shipped with the code in public/ (served as /media/events/…, independent of uploads). */
    public const ASSETS = 'media/events';

    /** Where earlier versions copied the covers on the public disk. */
    public const OLD_DIR = 'events/demo';

    /** @return list<array<string, mixed>> */
    public static function events(): array
    {
        return [
            [
                'title' => 'IIP Leadership Summit 2026',
                'slug' => 'leadership-summit',
                'starts_on' => '2026-11-21',
                'location' => 'Kolkata',
                'summary' => 'A day of keynotes and round tables with business leaders, educators and the IIP Director Council.',
                'description' => "## About the summit\n\nThe Leadership Summit brings members of the Brain Bank together with founders, senior professionals and educators for a day of keynotes, panel discussions and small-group round tables.\n\n## Programme\n\n- **10:00** Welcome and opening keynote\n- **11:30** Panel: building careers that last\n- **14:00** Round tables by industry\n- **16:30** Networking tea\n\n## Who should attend\n\nMembers, mentors, partner institutions and anyone who wants to grow their professional network.",
                'button_text' => 'Reserve a seat',
                'image' => 'leadership-summit.jpg',
            ],
            [
                'title' => 'Hunar Career Fair',
                'slug' => 'hunar-career-fair',
                'starts_on' => '2026-12-05',
                'location' => 'Kolkata',
                'summary' => 'Meet recruiters, training partners and mentors — with on-the-spot CV reviews and mock interviews.',
                'description' => "## Find your next step\n\nThe Hunar Career Fair connects job seekers and students with employers and training partners from across the IIP network.\n\n## At the fair\n\n- Recruiter stalls across IT, healthcare, hospitality and design\n- Free CV reviews by IIP mentors\n- Mock interviews with feedback\n- Talks on skill certification and apprenticeships\n\n## Bring along\n\nA few printed copies of your CV and your certificates.",
                'button_text' => 'Register to attend',
                'image' => 'hunar-career-fair.jpg',
            ],
            [
                'title' => 'Khazana MSME Finance Clinic',
                'slug' => 'khazana-msme-finance-clinic',
                'starts_on' => '2026-12-12',
                'location' => 'Online',
                'summary' => 'One-to-one sessions with lenders and advisers on loans, government schemes and growth capital for small businesses.',
                'description' => "## Finance made simple\n\nBook a 20-minute slot with lenders and advisers from the Khazana network and get practical answers about funding your business.\n\n## Topics\n\n- Working-capital and term loans\n- Government schemes for MSMEs\n- Preparing a lender-ready business plan\n- Equity and angel funding basics\n\n## How it works\n\nRegister interest and the IIP team will share the slot timings and the joining link.",
                'button_text' => 'Book a slot',
                'image' => 'khazana-msme-finance-clinic.jpg',
            ],
            [
                'title' => 'Health & Wellness Camp',
                'slug' => 'health-wellness-camp',
                'starts_on' => '2027-01-10',
                'location' => 'Kolkata',
                'summary' => 'Free health check-ups, nutrition advice and a yoga session, organised with the IIP healthcare network.',
                'description' => "## A day for your wellbeing\n\nDoctors, nutritionists and wellness coaches from the IIP healthcare network offer free check-ups and advice.\n\n## Included\n\n- Blood pressure, sugar and BMI screening\n- Eye and dental check-ups\n- Nutrition counselling\n- Morning yoga and mindfulness session\n\nOpen to members, their families and the local community.",
                'button_text' => 'Register interest',
                'image' => 'health-wellness-camp.jpg',
            ],
            [
                'title' => 'Skill India Webinar: Future-Ready Skills',
                'slug' => 'skill-india-webinar',
                'starts_on' => '2027-01-24',
                'location' => 'Online',
                'summary' => 'Which skills employers will look for next year — and how to get certified for them through Skill India programmes.',
                'description' => "## What you will learn\n\n- The skills in demand across industries\n- Certification paths through Skill India and NSDC partners\n- How IIP courses and tests fit in\n- Live Q&A with industry mentors\n\n## Format\n\nA 90-minute live webinar. A recording is shared with everyone who registers.",
                'button_text' => 'Save my place',
                'image' => 'skill-india-webinar.jpg',
            ],
            [
                'title' => 'CSR & Philanthropy Roundtable',
                'slug' => 'csr-philanthropy-roundtable',
                'starts_on' => '2027-02-13',
                'location' => 'Kolkata',
                'summary' => 'Companies, NGOs and donors meet to plan community projects in education, health and livelihoods.',
                'description' => "## Partnering for impact\n\nThe roundtable connects CSR teams and donors with NGOs and community organisations in the IIP network.\n\n## Agenda\n\n- Community needs in education, health and livelihoods\n- Case studies of successful partnerships\n- Matching sessions between donors and NGOs\n\n## Who should attend\n\nCSR heads, foundation trustees, NGO leaders and volunteers.",
                'button_text' => 'Request an invitation',
                'image' => 'csr-philanthropy-roundtable.jpg',
            ],
        ];
    }

    public static function added(): bool
    {
        return filled(Setting::query()->where('key', self::MARKER)->value('value'));
    }

    /** Adds the sample events unless they were added before. Returns how many were created. */
    public static function addEventsOnce(): int
    {
        if (self::added()) {
            return 0;
        }

        $created = 0;

        DB::transaction(function () use (&$created) {
            foreach (self::events() as $data) {
                if (Event::query()->where('slug', $data['slug'])->exists()) {
                    continue;
                }

                Event::query()->create([
                    ...$data,
                    'image' => self::cover($data['image']),
                    'button_url' => '#enquiry',
                    'is_active' => true,
                ]);
                $created++;
            }

            self::orderByDate();
            Setting::query()->updateOrCreate(['key' => self::MARKER], ['value' => now()->toIso8601String()]);
        });

        return $created;
    }

    /** Upcoming events first (soonest first), then undated / past ones in their current order. */
    public static function orderByDate(): void
    {
        $today = now()->startOfDay();
        $events = Event::query()->ordered()->get();
        [$upcoming, $rest] = $events->partition(fn (Event $e) => $e->starts_on && $e->starts_on->gte($today));

        $upcoming->sortBy(fn (Event $e) => $e->starts_on->timestamp)->values()
            ->concat($rest->values())
            ->each(fn (Event $e, int $i) => $e->sort_order === $i + 1 ?: $e->forceFill(['sort_order' => $i + 1])->saveQuietly());
    }

    /** Address of a bundled cover (/media/events/…), null if the file is missing. */
    public static function cover(string $file): ?string
    {
        return File::exists(public_path(self::ASSETS.'/'.$file)) ? '/'.self::ASSETS.'/'.$file : null;
    }

    /**
     * Sample events still showing a cover copied by an earlier version (events/demo/… on the public disk)
     * use the bundled file instead, so the cover can no longer go missing.
     */
    public static function useBundledCovers(): int
    {
        $count = 0;
        foreach (Event::query()->where('image', 'like', self::OLD_DIR.'/%')->get() as $event) {
            if ($cover = self::cover(basename((string) $event->image))) {
                $old = (string) $event->image;
                $event->forceFill(['image' => $cover])->saveQuietly();
                Storage::disk('public')->delete($old);
                $count++;
            }
        }

        return $count;
    }
}
