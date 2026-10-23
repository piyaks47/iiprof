<?php

namespace App\Support;

use App\Models\HomeItem;
use App\Models\HomeSection;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Starting content for the newer homepage blocks (feature tiles, impact numbers, membership benefits
 * and categories, programmes, testimonials, registrations, FAQ and the join banner).
 *
 * The block layout follows the homepage of a member institute (tiles → benefits → numbers → categories
 * → programmes → voices → registrations → FAQ → join); the texts and figures are IIP's own, taken from
 * iiprof.com (About Us, Community, Membership, Book My Seat, Credibility). Everything is edited under
 * Homepage in the admin; seedOnce() runs once per installation, so deleted items stay deleted.
 */
class HomeBlocks
{
    public const MARKER = 'home_blocks';

    /** Order of all blocks on a homepage that still has the original order. */
    public const DEFAULT_ORDER = [
        'hero', 'stats', 'pillars', 'about', 'highlights', 'benefits', 'impact', 'membership',
        'programmes', 'events', 'gallery', 'testimonials', 'partners', 'faq', 'join', 'contact',
    ];

    /** Order of the blocks before this version. */
    private const ORIGINAL_ORDER = ['hero', 'stats', 'about', 'highlights', 'events', 'gallery', 'contact'];

    private const MEDIA = '/media/menu/';

    /** @return array<string, array<string, ?string>> section texts */
    public static function sections(): array
    {
        return [
            'pillars' => [
                'eyebrow' => 'One platform', 'title' => 'Everything a professional', 'title_accent' => 'needs',
                'subtitle' => 'Six programmes under one membership — learn, earn, borrow and invest, stay healthy, get expert advice and give back.',
            ],
            'impact' => [
                'eyebrow' => 'IIP in numbers', 'title' => 'A growing brain bank of', 'title_accent' => 'professionals',
                'subtitle' => 'A not-for-profit institute bringing every profession onto one platform since 2012.',
                'image' => self::MEDIA.'more-events.webp',
            ],
            'benefits' => [
                'eyebrow' => 'Membership facilities', 'title' => 'What you get as an', 'title_accent' => 'IIP member',
                'subtitle' => 'Facilities open to members across every profession, from students to senior professionals and organisations.',
                'button_text' => 'See membership grades', 'button_url' => '/membership',
            ],
            'membership' => [
                'eyebrow' => 'Join IIP', 'title' => 'Choose your', 'title_accent' => 'membership',
                'subtitle' => 'Fifteen grades for professionals, students, senior citizens, NRIs and organisations. These are the most popular.',
                'button_text' => 'Compare all 15 grades', 'button_url' => '/membership',
            ],
            'programmes' => [
                'eyebrow' => 'Learn & grow', 'title' => 'Programmes &', 'title_accent' => 'initiatives',
                'subtitle' => 'Admissions, training, enterprise and community programmes run by IIP and its partners.',
                'button_text' => 'Book my seat', 'button_url' => '/hunar/book-my-seat',
            ],
            'testimonials' => [
                'eyebrow' => 'Member voices', 'title' => 'What our members', 'title_accent' => 'say',
                'subtitle' => 'Professionals and students on learning and growing with IIP.',
                'button_text' => 'All testimonials', 'button_url' => '/testimonials',
            ],
            'partners' => [
                'eyebrow' => 'Recognised & registered', 'title' => 'Registrations and approvals',
                'subtitle' => 'The certificates are available on the Credibility page.',
            ],
            'faq' => [
                'eyebrow' => 'Questions', 'title' => 'Frequently asked', 'title_accent' => 'questions',
                'subtitle' => 'Can’t find your answer? Send us a query and the IIP team will get back to you.',
                'button_text' => 'Ask a question', 'button_url' => '#enquiry',
            ],
            'join' => [
                'eyebrow' => 'Become a member', 'title' => 'Grow professionally while', 'title_accent' => 'helping others grow',
                'subtitle' => 'Join the Brain Bank of Global Talents — learn from experts, offer your services and build your network.',
                'button_text' => 'Register as a member', 'button_url' => '#register',
                'image' => self::MEDIA.'community.webp',
            ],
        ];
    }

    /** @return array<string, list<array<string, ?string>>> section => items */
    public static function items(): array
    {
        $m = self::MEDIA;

        return [
            'pillars' => [
                ['title' => 'Hunar', 'subtitle' => 'Education & skills', 'icon' => 'academic-cap', 'image' => $m.'hunar.webp', 'button_text' => 'Explore Hunar', 'button_url' => '/hunar/book-my-seat',
                    'description' => 'Admissions from playschool to PhD, skill training and scholarships of up to 50% through Book My Seat.'],
                ['title' => 'Jeevika', 'subtitle' => 'Jobs & enterprise', 'icon' => 'briefcase', 'image' => $m.'jeevika.webp', 'button_text' => 'Explore Jeevika', 'button_url' => '/jeevika/experience',
                    'description' => 'Jobs for freshers, professionals and freelancers, work from home, and support for entrepreneurs.'],
                ['title' => 'Khazana', 'subtitle' => 'Finance', 'icon' => 'banknotes', 'image' => $m.'khazana.webp', 'button_text' => 'Explore Khazana', 'button_url' => '/industry/banks-nbfc',
                    'description' => 'Lenders, borrowers, depositors and fund managers on one platform — from SHGs and microfinance to venture capital.'],
                ['title' => 'Health', 'subtitle' => 'Healthcare', 'icon' => 'heart', 'image' => $m.'health.webp', 'button_text' => 'Explore Health', 'button_url' => '/industry/hospitals',
                    'description' => 'Doctors, hospitals, diagnostics and health workers, with patient history and organ-donation registers.'],
                ['title' => 'Consultancy', 'subtitle' => 'Expert advice', 'icon' => 'light-bulb', 'image' => $m.'consultancy.webp', 'button_text' => 'Request consultancy', 'button_url' => '/industry/request-consultancy',
                    'description' => 'Finance, legal, IT, education, staffing and technical consultants — or ask for a consultation.'],
                ['title' => 'Philanthropy', 'subtitle' => 'Giving back', 'icon' => 'gift', 'image' => $m.'philanthropy.webp', 'button_text' => 'Explore Philanthropy', 'button_url' => '/industry/fund-raising',
                    'description' => 'Donors, CSR funds, grants and volunteers connected with NGOs, implementing agencies and beneficiaries.'],
            ],
            'impact' => [
                ['value' => '2012', 'title' => 'Founded', 'description' => 'Incorporated as a not-for-profit under section 25 of the Companies Act, 1956.'],
                ['value' => '15', 'title' => 'Membership grades', 'description' => 'From student and associate to fellow, corporate and international members.'],
                ['value' => '70+', 'title' => 'Professional categories', 'description' => 'Universities, hospitals, NBFCs, MSMEs, NGOs, consultants and many more.'],
                ['value' => '6', 'title' => 'Programmes', 'description' => 'Hunar, Jeevika, Khazana, Health, Consultancy and Philanthropy.'],
            ],
            'benefits' => [
                ['title' => 'Professional network', 'icon' => 'user-group', 'description' => 'Member portfolios, smart search, chat, mass invitations and broadcasting to reach the right people.'],
                ['title' => 'Events & live sessions', 'icon' => 'calendar', 'description' => 'Seminars, member interaction sessions, live events, audio and video conferences.'],
                ['title' => 'Skills & certification', 'icon' => 'academic-cap', 'description' => 'Up-skilling and re-skilling, skill-competency mapping and guidance from industry experts.'],
                ['title' => 'Recognition & awards', 'icon' => 'star', 'description' => 'Talent register, books of records, prizes and awards, rankings and ratings.'],
                ['title' => 'Research & publications', 'icon' => 'book-open', 'description' => 'Research journals and the chance to publish your work.'],
                ['title' => 'Careers & placement', 'icon' => 'briefcase', 'description' => 'Jobs, on-the-job training, internships, placement and third-party payroll services.'],
                ['title' => 'Access to funding', 'icon' => 'banknotes', 'description' => 'Angel funding, venture and equity capital, loans and subsidised capital through Khazana.'],
                ['title' => 'Health monitoring', 'icon' => 'heart', 'description' => 'A 360-degree health monitoring programme and community health cover.'],
                ['title' => 'Promotion & marketplace', 'icon' => 'megaphone', 'description' => 'Advertising and paid promotions, and buying and selling through DuniyaBazar.'],
            ],
            'membership' => [
                ['title' => 'Student Member', 'value' => 'STIIP', 'subtitle' => 'Students aged 18+', 'button_text' => 'Apply now', 'button_url' => '#register',
                    'description' => 'For students at a recognised institute who want to grow into skilled professionals.'],
                ['title' => 'Associate Member', 'value' => 'AMIIP', 'subtitle' => 'Age 25+, 3 years of work', 'button_text' => 'Apply now', 'button_url' => '#register',
                    'description' => 'For professionals early in their career (age 19+ for IIP exam alumni). No voting rights.'],
                ['title' => 'Member', 'value' => 'MIIP', 'subtitle' => 'Age 30+, 5 years in the profession', 'button_text' => 'Apply now', 'button_url' => '#register',
                    'description' => 'Full membership for established professionals (age 25+ with 3 years for IIP exam alumni).'],
                ['title' => 'Fellow Member', 'value' => 'FMIIP', 'subtitle' => '10+ years of professional standing', 'button_text' => 'Apply now', 'button_url' => '#register',
                    'description' => 'For professionals of repute, or IIP members of five years or more with notable achievements.'],
                ['title' => 'Corporate Member', 'value' => 'CMIIP', 'subtitle' => 'Companies and firms', 'button_text' => 'Apply now', 'button_url' => '#register',
                    'description' => 'Organisations join through an authorised person and can nominate up to three people.'],
                ['title' => 'Senior Citizen Member', 'value' => 'SCIIP', 'subtitle' => 'Age 60+', 'button_text' => 'Apply now', 'button_url' => '#register',
                    'description' => 'For experienced professionals who want to keep contributing; approved by the Managing Committee.'],
            ],
            'programmes' => [
                ['title' => 'Book My Seat', 'subtitle' => 'Admissions', 'image' => $m.'hunar-book-my-seat.webp', 'button_text' => 'Book my seat', 'button_url' => '/hunar/book-my-seat',
                    'description' => 'Reserve your admission — from playschool to PhD, ITI and polytechnic — with batch sizes from 1:1 to 1:32.'],
                ['title' => 'Scholarship test', 'subtitle' => 'Scholarships', 'image' => $m.'edu10.webp', 'button_text' => 'Find out more', 'button_url' => '/events/scholarship-test',
                    'description' => 'Take the online scholarship test and earn a scholarship on your course fee.'],
                ['title' => 'Skill development', 'subtitle' => 'Training', 'image' => $m.'jeevika-jobs.webp', 'button_text' => 'Ask about training', 'button_url' => '#enquiry',
                    'description' => 'Vocational, soft-skill, employability and leadership training — fee-based or free.'],
                ['title' => 'Entrepreneurship support', 'subtitle' => 'Enterprise', 'image' => $m.'jeevika-entrepreneur.webp', 'button_text' => 'Learn more', 'button_url' => '/industry/entrepreneur',
                    'description' => 'Entrepreneurship development with 360-degree handholding, incubation and access to funding.'],
                ['title' => 'Talent register', 'subtitle' => 'Recognition', 'image' => $m.'talent-register.webp', 'button_text' => 'Learn more', 'button_url' => '/talent-register',
                    'description' => 'Record your skills and achievements, and get graded, rated and recognised.'],
                ['title' => 'Community outreach', 'subtitle' => 'Philanthropy', 'image' => $m.'more-campaigns.webp', 'button_text' => 'Volunteer', 'button_url' => '/industry/volunteer-service',
                    'description' => 'Awareness campaigns, donations, CSR projects and volunteer service with partner NGOs.'],
            ],
            'partners' => [
                ['title' => 'NITI Aayog', 'subtitle' => 'NGO registration', 'button_url' => '/credibility'],
                ['title' => 'MSME Udyam', 'subtitle' => 'Udyam registration', 'button_url' => '/credibility'],
                ['title' => 'Income Tax 12A', 'subtitle' => 'Section 12A registration', 'button_url' => '/credibility'],
                ['title' => 'Income Tax 80G', 'subtitle' => 'Section 80G approval', 'button_url' => '/credibility'],
                ['title' => 'CSR', 'subtitle' => 'CSR registration certificate', 'button_url' => '/credibility'],
                ['title' => 'Swarojgar Yojana', 'subtitle' => 'Government of West Bengal', 'button_url' => '/credibility'],
            ],
            'faq' => [
                ['title' => 'What is the International Institute of Professionals?',
                    'description' => 'IIP is a not-for-profit company incorporated in 2012 under section 25 of the Companies Act, 1956. It brings professionals of every field onto one platform — a brain bank of talents that up-skills people and opens up new income opportunities.'],
                ['title' => 'Who can become a member?',
                    'description' => 'Professionals, students (18+), senior citizens, NRIs and international professionals, as well as companies, institutions and societies. There are 15 membership grades — see [Membership](/membership) for who each one is for.'],
                ['title' => 'How do I register?',
                    'description' => 'Click **Register** at the top of any page and fill in the form. The IIP team checks your details and sets up your profile under the right grade.'],
                ['title' => 'What is Book My Seat?',
                    'description' => 'A Hunar service that reserves your admission — from playschool to PhD, ITI, polytechnic and skill training — with batch sizes from 1:1 to 1:32 and scholarships of up to 50% based on an online exam. [Book your seat](/hunar/book-my-seat).'],
                ['title' => 'What if my admission is not accepted?',
                    'description' => 'If an institution does not accept your application, the admission fee is refunded in full within 15 days.'],
                ['title' => 'Is IIP registered for tax and CSR purposes?',
                    'description' => 'Yes. IIP holds 12A and 80G registrations under the Income Tax Act, a CSR registration certificate, and NITI Aayog and MSME Udyam registrations. The documents are on the [Credibility](/credibility) page.'],
                ['title' => 'Can organisations work with IIP?',
                    'description' => 'Yes. Companies, institutions and societies can join as corporate, institutional or society members (with up to three nominees), offer jobs and consultancy, or fund CSR projects through Philanthropy.'],
            ],
        ];
    }

    public static function seeded(): bool
    {
        return filled(Setting::query()->where('key', self::MARKER)->value('value'));
    }

    /** @return list<string> blocks that got content */
    public static function seedOnce(): array
    {
        if (self::seeded()) {
            return [];
        }

        $done = [];
        DB::transaction(function () use (&$done) {
            HomeSection::ensureAll();
            $items = self::items();

            foreach (self::sections() as $key => $texts) {
                $section = HomeSection::query()->where('key', $key)->first();
                if (! $section) {
                    continue;
                }
                if (blank($section->title) && blank($section->eyebrow)) {
                    $section->forceFill($texts)->saveQuietly();
                    $done[] = $key;
                }
                if (isset($items[$key]) && ! HomeItem::query()->in($key)->exists()) {
                    foreach ($items[$key] as $i => $item) {
                        HomeItem::query()->create(['section' => $key, 'sort_order' => $i + 1, 'is_active' => true, ...$item]);
                    }
                    $done[] = $key.' items';
                }
            }

            self::placeNewBlocks();
            Setting::query()->updateOrCreate(['key' => self::MARKER], ['value' => now()->toIso8601String()]);
        });

        return $done;
    }

    /**
     * New blocks go between the existing ones (DEFAULT_ORDER) — only on a homepage whose blocks are still in
     * the original order. Otherwise they stay at the end, where they were added, and the order set in the
     * admin is kept.
     */
    private static function placeNewBlocks(): void
    {
        $current = HomeSection::query()->ordered()->pluck('key')->all();
        $old = array_values(array_intersect($current, self::ORIGINAL_ORDER));

        if ($old !== self::ORIGINAL_ORDER) {
            return;
        }

        $position = array_flip(self::DEFAULT_ORDER);
        foreach (HomeSection::query()->get() as $section) {
            $order = ($position[$section->key] ?? count($position) + $section->sort_order) + 1;
            if ($section->sort_order !== $order) {
                $section->forceFill(['sort_order' => $order])->saveQuietly();
            }
        }
    }
}
