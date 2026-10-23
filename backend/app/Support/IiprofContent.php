<?php

namespace App\Support;

use App\Enums\PageStatus;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\FooterColumn;
use App\Models\GalleryItem;
use App\Models\HeroSlide;
use App\Models\Highlight;
use App\Models\HomeSection;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Content of the current https://iiprof.com site, imported into this CMS.
 *
 * Menus, links, names, roles, dates, contact details and image addresses are taken over as they are
 * (old URLs such as /about-us or /industry/universities keep working). Longer texts are short
 * summaries — paste the original wording into the pages in the admin where you want it verbatim.
 * Images point at iiprof.com until `php artisan iiprof:localize-images` copies them to this server
 * (the deploy tool runs it automatically).
 *
 * import() fills empty blocks and replaces the starter content shipped with earlier versions;
 * anything changed in the admin is kept unless $force is true.
 */
class IiprofContent
{
    public const SOURCE_HOST = 'iiprof.com';

    private const IMG = 'https://iiprof.com/public/';

    public const ADDRESS = "Asia Pacific Head Office\n152, S.P. Mukherjee Road, Abhishek Point Building, 5th Floor\nKolkata 700026, West Bengal, India";

    /** Settings row that records the import, so later deploys never overwrite admin edits. */
    public const MARKER = 'content_import';

    public const VERSION = 'iiprof.com/2026-10b';

    /** md5 of page texts shipped by earlier versions (so they still count as not edited). */
    public const OLDER_PAGE_HASHES = [
        'about-us' => ['c4fe3d12fa78114afbafcc0c705742c8'],
        'credibility' => ['4eb28566d49ed5215e0050626256c910'],
    ];

    /** Address shipped by the earlier starter seeder. */
    private const STARTER_ADDRESS = "Asia Pacific Head Office\n152, S.P. Mukherjee Road, Abhishek Point Building, 5th Floor\nKolkata 700026";

    // ------------------------------------------------------------------
    // Data
    // ------------------------------------------------------------------

    /** @return array<string, ?string> */
    public static function settings(): array
    {
        return [
            'site_name' => 'International Institute of Professionals',
            'short_name' => 'IIP',
            'tagline' => 'Brain Bank of Global Talents',
            'meta_description' => 'International Institute of Professionals (IIP) — a not-for-profit brain bank of global talents, founded in 2012, that upskills professionals and brings education, careers, finance, health and philanthropy onto one platform.',
            'contact_email' => 'info@iiprof.com',
            'address' => self::ADDRESS,
            'facebook_url' => 'https://www.facebook.com/International-Institute-of-Professionals-100368698839489',
            'linkedin_url' => 'https://www.linkedin.com/company/international-institute-of-professionals-iip/about/',
            'youtube_url' => 'https://www.youtube.com/channel/UC2DUPf9gNac465I7enFXezg',
            'x_url' => 'https://twitter.com/IIPOrganization',
            'logo' => self::IMG.'img/logo-black.png',
            'default_banner' => self::IMG.'img/home-bg-2.jpg',
            'login_logo' => self::IMG.'img/logo.png',
            'login_background' => self::IMG.'img/bg-login.jpg',
            'footer_text' => 'All rights reserved.',
        ];
    }

    /**
     * Navigation exactly as on iiprof.com: [label, url or null, children, new tab].
     *
     * @return list<array{0: string, 1: ?string, 2: array, 3?: bool}>
     */
    public static function menu(): array
    {
        $leaves = fn (string $prefix, array $items) => array_map(
            fn ($item) => is_array($item) ? [$item[0], $item[1], []] + (isset($item[2]) ? [3 => $item[2]] : []) : [$item, $prefix.Str::slug(str_replace('/', ' ', $item)), []],
            $items,
        );
        $dbc = 'http://duniyaabazar.com/';

        return [
            ['Home', '/', []],
            ['About Us', null, [
                ['About IIP', '/about-us', []],
                ['Management', null, [
                    ['Director Council', '/management/director-council', []],
                    ['Governing Body', '/management/governing-body', []],
                    ['Managing Committee', '/management/managing-committee', []],
                    ['Board of Studies', '/management/board-of-studies', []],
                    ['Advisory Council', '/management/advisory-council', []],
                ]],
                ['Credibility', '/credibility', []],
                ['Testimonials', '/testimonials', []],
            ]],
            ['Community', '/industry/alumni-community', []],
            ['Hunar', null, [
                ['Book My Seat', '/hunar/book-my-seat', []],
                ['World-Edu', null, $leaves('/industry/', ['Universities', 'Colleges', 'Schools', 'Institutions', 'Tutorials',
                    'Primary School', 'Pre-Primary School', 'Coach-Mentor', 'Education Consultant'])],
            ]],
            ['Jeevika', null, [
                ['Jobs', null, $leaves('/jeevika/', [
                    ['Experience', '/jeevika/experience'], ['Fresher', '/jeevika/fresher'], ['Freelance', '/jeevika/freelencer'],
                    ['Work From Home', '/jeevika/wfh'], ['Professional', '/jeevika/professonal'], ['Skilled', '/jeevika/skilled'],
                    ['Un-skilled', '/jeevika/un-skilled'],
                ])],
                ['Entrepreneur', '/industry/entrepreneur', []],
            ]],
            ['Khazana', null, [
                ['Lender', null, $leaves('/industry/', ['Banks/NBFC', 'Microfinance', 'Pvt Equity', 'Venture Capital', 'FDI', 'CSR', 'Grants'])],
                ['Borrower', null, $leaves('/industry/', ['Corporate', 'MSME', 'Personal', 'SHG', 'JLG', 'Co-operative', 'NGO'])],
                ['Depositor', null, $leaves('/industry/', ['IPO', 'Equity Share', 'Bond', 'Debenture', 'Insurance', 'Fixed Deposit', 'Mutual Fund'])],
                ['Fund Manager', null, $leaves('/industry/', ['Portfolio Manager', 'DSA', 'Insurance Advisor'])],
            ]],
            ['DuniyaBazar', $dbc, [], true],
            ['Bookings', '/bookings', []],
            ['Consultancy', null, [
                ['Consultant', null, $leaves('/industry/', ['Finance', 'Education', ['IT & ITeS', '/industry/it-ites'], 'Legal',
                    'Staffing Solution', 'Technical', 'Wellness', 'Commerce', 'Media'])],
                ['Request Consultancy', '/industry/request-consultancy', []],
            ]],
            ['Health', null, [
                ['Patient', null, $leaves('/industry/', [['Patient', '/industry/patients'], 'Organ Donation', 'Organ Recipient'])],
                ['Doctor', null, $leaves('/industry/', ['Homeopathy', 'Allopath', 'Ayurvedic', 'Unani', 'Patient History'])],
                ['Health Worker', '/industry/health-worker', []],
                ['Hospitals', '/industry/hospitals', []],
                ['Diagnostic', '/industry/diagnostic', []],
                ['Blood Bank', $dbc, [], true],
                ['Medicine', $dbc, [], true],
                ['Medical Equipments', $dbc, [], true],
            ]],
            ['Talent-Register', '/talent-register', []],
            ['Philanthropy', null, [
                ['Donor', null, $leaves('/industry/', ['Fund Raising', 'Commodity', 'Volunteer Service', ['CSR', '/industry/donor-csr'],
                    ['Grant', '/industry/donor-grant'], 'Implementing Agency'])],
                ['Donee', null, $leaves('/industry/', [['NGO/CBO', '/industry/ngo-cbo'], 'Individual', 'Beneficiary'])],
                ['Profile Creation', '/industry/profile-creation', []],
            ]],
            ['Edu10', '/edu10', []],
            ['More', null, [
                ['Gallery', '/view-gallery', []],
                ['Events', '/view-events', []],
                ['Campaigns', '/campaign', []],
                ['R&D', '/industry/rnd', []],
                ['Contact Us', '/contact-us', []],
            ]],
        ];
    }

    /** @return array<string, list<array{0: string, 1: string, 2?: bool}>> */
    public static function footer(): array
    {
        return [
            'Useful Links' => [['About Us', '/about-us'], ['Careers', '/how-it-works'], ['Membership', '/membership'], ['Inquiry', '/contact-us']],
            'Related Links' => [
                ['EXIN', 'http://exteriorinteriors.com', true], ['DBC', 'http://duniyaabazar.com/', true],
                ['Skill India', 'https://www.skillindia.gov.in/', true], ['National Skill Development Corpn', 'https://nsdcindia.org/', true],
            ],
            'Other Links' => [
                ['Terms & Condition', '/terms-conditions'], ['Privacy Policy', '/privacy-policy'],
                ['Disclaimer Policy', '/disclaimer-policy'], ['Cancellation & Refund Policy', '/cancellation-refund-policy'],
            ],
        ];
    }

    /** Slides in the order of iiprof.com, with their banner photos. */
    public static function heroSlides(): array
    {
        return [
            ['eyebrow' => 'International Institute of Professionals', 'title' => 'Expand Community Outreach',
                'subtitle' => 'Share your expertise with a wider community and keep learning from leaders in your field.',
                'button_text' => 'Join the community', 'button_url' => '#register', 'image' => self::IMG.'uploads/images/original/banner_1684929818.jpg'],
            ['eyebrow' => 'International Institute of Professionals', 'title' => 'Accelerate Your Career Growth',
                'subtitle' => 'Build your skills through IIP’s education programmes and meet our talent-acquisition partners.',
                'button_text' => 'Book my seat', 'button_url' => '/hunar/book-my-seat', 'image' => self::IMG.'uploads/images/original/banner_1684929645.jpg'],
            ['eyebrow' => 'International Institute of Professionals', 'title' => 'Grow Your Network',
                'subtitle' => 'Connect and work with peers and professionals from across the industry.',
                'button_text' => 'Become a member', 'button_url' => '#register', 'image' => self::IMG.'uploads/images/original/banner_1684929832.jpg'],
        ];
    }

    public static function announcements(): array
    {
        return [
            ['Membership registration is open — join the Brain Bank of Global Talents.', '#register'],
            ['Book My Seat: scholarships of up to 50% on admissions, based on an online test.', '/hunar/book-my-seat'],
            ['Hunar · Jeevika · Khazana · Health · Consultancy · Philanthropy — one platform for every professional.', '/about-us'],
        ];
    }

    public static function gallery(): array
    {
        return [
            ['Meeting with MSME & Pradipta Kumar Roy', 'https://iiprof.com/public/uploads/gallery/original/gallery_1678270426.jpeg'],
            ['Pradipta Kumar Roy Accreditation', 'https://iiprof.com/public/uploads/gallery/original/gallery_1678270247.jpeg'],
            ['Seminar', 'https://iiprof.com/public/uploads/gallery/original/gallery_1678269622.jpg'],
            ['Seminar', 'https://iiprof.com/public/uploads/gallery/original/gallery_1677072224.jpg'],
        ];
    }

    public static function event(): array
    {
        return [
            'title' => 'Scholarship Test',
            'slug' => 'scholarship-test',
            'summary' => 'Score well in the IIP scholarship test and get up to 90% scholarship on our interior design courses.',
            'description' => "## Test dates\n\n26 February and 5 March, from 11:30 am.\n\n## The scholarship\n\nUp to **90%** of the course fee for IIP’s interior design courses, depending on your test score.\n\n## How to take part\n\nUse **Register interest** and the IIP team will send you the test details.",
            'image' => self::IMG.'uploads/images/original/event_1684929230.jpg',
            'location' => 'Kolkata',
            'button_text' => 'Register interest',
            'button_url' => '#enquiry',
        ];
    }

    /** @return list<array{0: string, 1: string, 2: ?string, 3: ?string}> group, name, role, photo */
    public static function team(): array
    {
        $photo = self::IMG.'uploads/team/thumb/';

        return [
            ['director-council', 'Mr. Chandan Roy', 'Project Management Consultant', $photo.'Mr.%20Chandan%20Roy__1679908912.jpg'],
            ['director-council', 'Dr. Neelam Saxena', 'Professor & Head, ACED', $photo.'Dr.%20Neelam%20Saxena__1649941927.png'],
            ['director-council', 'Pradipta Kumar Roy', 'International Sports Personality, India Representative, GCS International', $photo.'Pradipta%20Kumar_Roy_1680265924.jpg'],
            ['director-council', 'Ruma Roy Chowdhury', 'International Sports Personality, MD, InfraBasic India', $photo.'Ruma_Roy%20Chowdhury_1680265808.jpg'],
            ['director-council', 'Shreyashi Roy', 'Interior Designer & Director, STUDIO HABITAT', $photo.'Shreyashi_Roy_1683280634.jpg'],
            ['governing-body', 'Mr. Avinash Baruli', null, self::IMG.'images/iip.jpg'],
        ];
    }

    /** Names and photos only — add each person's own words in Admin → Testimonials. */
    public static function testimonials(): array
    {
        $photo = self::IMG.'uploads/testimonials/original/';

        return [
            ['Ayushi More', null, $photo.'Ayushi%20More_1649771211.jpg'],
            ['Taufeek Ahmad', 'https://www.youtube.com/watch?v=G04IWiW4JNg', $photo.'Taufeek%20Ahmad_1649771426.png'],
            ['Deepali Fialoke', self::IMG.'uploads/testimonials/videos/Suraj%20Jhunjhunwala_1649770957.mp4', $photo.'Deepali%20Fialoke_1649771303.png'],
        ];
    }

    /** Homepage section texts. */
    public static function sections(): array
    {
        return [
            'hero' => ['button_text' => 'Discover IIP', 'button_url' => '/about-us'],
            'stats' => ['eyebrow' => 'At a glance', 'title' => 'One institute, every profession'],
            'highlights' => ['eyebrow' => 'Why IIP', 'title' => 'Brain Bank of Global Talents', 'subtitle' => 'Grow professionally while helping others grow.', 'image' => self::IMG.'img/banner-bg.jpg'],
            'events' => ['eyebrow' => 'What’s on', 'title' => 'IIP', 'title_accent' => 'Events', 'subtitle' => 'Programmes, tests and gatherings for our members and partners.'],
            'gallery' => ['eyebrow' => 'Moments', 'title' => 'Our', 'title_accent' => 'Gallery', 'subtitle' => 'Meetings, accreditations and seminars at IIP.'],
            'about' => [
                'eyebrow' => 'Who we are', 'title' => 'About', 'title_accent' => 'Us',
                'subtitle' => 'A not-for-profit brain bank of global talents, founded in 2012.',
                'body' => "IIP upskills professionals and brings them onto one platform — education and admissions through **Hunar**, jobs and entrepreneurship through **Jeevika**, lending, borrowing and investing through **Khazana**, plus health, consultancy and philanthropy.\n\nMembers learn from experts, offer their services to the community and grow their network while helping others grow.",
                'button_text' => 'Register as a Member', 'button_url' => '#register',
                'image' => self::IMG.'uploads/files/about-img2-new-2.png',
            ],
            'contact' => [
                'eyebrow' => 'Our mission', 'title' => 'Let’s build what’s next, together',
                'body' => 'To enrich knowledge and skills, give every professional a common platform, and work across professions for social and environmental upliftment.',
                'subtitle' => 'International Institute of Professionals',
                'button_text' => 'Submit a query', 'button_url' => '#enquiry',
                'image' => self::IMG.'img/home-bg.jpg',
            ],
        ];
    }

    /**
     * Every iiprof.com file this content uses (images, documents, videos) — so that copies on this server
     * can be re-fetched if they go missing.
     *
     * @return list<string>
     */
    public static function mediaUrls(): array
    {
        $json = json_encode([self::settings(), self::heroSlides(), self::gallery(), self::event(), self::team(),
            self::testimonials(), self::sections(), self::pages()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            .IiprofPages::json();
        preg_match_all('~https?://(?:www\.)?iiprof\.com/[^"\s)\\\\]+\.(?:jpe?g|png|gif|webp|svg|pdf|mp4|webm)~i', (string) $json, $m);

        return array_values(array_unique($m[0]));
    }

    /** @return array<string, array{title: string, excerpt: string, content: string, image?: ?string}> slug => page */
    public static function pages(): array
    {
        $img = self::IMG.'img/';

        return [
            'about-us' => [
                'title' => 'About Us',
                'excerpt' => 'A not-for-profit brain bank of professionals, founded in 2012 to upskill people and bring them onto one platform.',
                'image' => null, // banner: the default (as on iiprof.com); the photos are in the text
                'content' => <<<'MD'
![A seedling growing in the sun](https://iiprof.com/public/img/imp-3.jpg)

## Know IIP

The **International Institute of Professionals (IIP)** was incorporated in 2012 as a not-for-profit company under section 25 of the Companies Act, 1956. It works as a brain bank of professionals — a place where experienced people, learners and organisations meet to share skills and opportunities.

**Registrations:** 12A and 80G (income tax), CSR certificate, NITI Aayog and MSME Udyam.

![Hands joined together](https://iiprof.com/public/uploads/files/hands-1917895.png)

## Vision

A pool of professionally driven people that industry and corporates can rely on, and a nodal organisation for good governance and sustainable growth.

## Mission

- Social and environmental upliftment
- Richer knowledge and skills across professions
- One common platform for professionals
- Collaboration between professions

## What IIP does

- Vocational, soft-skill, leadership and self-employment training
- Events, webinars, job fairs and internships
- Consultancy, including business analysis and financial engineering
- Community insurance, annuity, pension and housing programmes
- Donation, grant and CSR fund-raising campaigns
- Talent register and skill grading
- Access to funding, including venture and subsidised capital
- Third-party payroll management
- Health monitoring
- Travel, hotel and ticket bookings
- A marketplace to buy and sell
- Environmental work such as tree planting and bio-fertilisers

## Objectives

- Be a brain bank of professionals
- Carry out research, development and advisory work
- Serve communities
- Create career and income opportunities
- Build a shared pool of human resources and commodities

![A team working together around a table](https://iiprof.com/public/img/about-img-3.jpg)
MD,
            ],
            'management-director-council' => ['title' => 'Director Council', 'excerpt' => 'The patrons who guide IIP’s direction.', 'content' => ''],
            'management-governing-body' => ['title' => 'Governing Body', 'excerpt' => 'The governing body of the International Institute of Professionals.', 'content' => ''],
            'management-managing-committee' => ['title' => 'Managing Committee', 'excerpt' => 'The committee that runs IIP day to day.', 'content' => ''],
            'management-board-of-studies' => ['title' => 'Board of Studies', 'excerpt' => 'The board that shapes IIP’s programmes and examinations.', 'content' => ''],
            'management-advisory-council' => ['title' => 'Advisory Council', 'excerpt' => 'Experts who advise IIP.', 'content' => ''],
            'credibility' => [
                'title' => 'Credibility',
                'excerpt' => 'Registrations, certificates and statutory documents of IIP.',
                'content' => <<<'MD'
## Documents and registrations

| No. | Document | |
| --- | --- | --- |
| 1 | Turnover certificate | [Download](https://iiprof.com/public/uploads/credibility/TURN%20OVER%20CERTIFICATE.pdf) |
| 2 | Balance sheet 2015–16 | [Download](https://iiprof.com/public/uploads/credibility/cre_1673261302.pdf) |
| 3 | Balance sheet 2016–17 | [Download](https://iiprof.com/public/uploads/credibility/cre_1673261302.pdf) |
| 4 | 80G sanction | [Download](https://iiprof.com/public/uploads/credibility/IIP%2080G%20Certificate.pdf) |
| 5 | 12A certificate | [Download](https://iiprof.com/public/uploads/credibility/IIP%2012A%20Certificate.pdf) |
| 6 | IIP Udyam (MSME) registration | [Download](https://iiprof.com/public/uploads/credibility/IIP-MSME%20UDYAM.pdf) |
| 7 | NITI Aayog registration | [Download](https://iiprof.com/public/uploads/credibility/NITI%20AYOG%20IIP.pdf) |
| 8 | CSR certificate | [Download](https://iiprof.com/public/uploads/credibility/CSR%20Certificate.PDF) |
| 9 | Swarojgar Yojana | [Download](https://iiprof.com/public/uploads/credibility/SWAROJGAR-WB.jpg) |
| 10 | MOA & AOA (Memorandum and Articles of Association) | [Download](https://iiprof.com/public/uploads/credibility/IIP%20MOA%20AOA.pdf) |
| 11 | IIP activity report | [Download](https://iiprof.com/public/uploads/credibility/cre_1673261302.pdf) |
| 12 | IIP annual report | [Download](https://iiprof.com/public/uploads/credibility/Anual%20Report_IIP.pdf) |
| 13 | Balance sheet 2017–18 | [Download](https://iiprof.com/public/uploads/credibility/cre_1673261302.pdf) |
| 14 | Balance sheet 2018–19 | [Download](https://iiprof.com/public/uploads/credibility/cre_1673261302.pdf) |
| 15 | Balance sheet 2021–22 | [Download](https://iiprof.com/public/uploads/credibility/cre_1673261302.pdf) |

![Swarojgar Yojana certificate](https://iiprof.com/public/uploads/credibility/SWAROJGAR-WB.jpg)

For anything else, send a query through the **Enquire** form.
MD,
            ],
            'testimonials' => ['title' => 'Testimonials', 'excerpt' => 'What our members say about learning and growing with IIP.', 'content' => ''],
            'membership' => [
                'title' => 'Membership',
                'excerpt' => 'Fifteen membership grades for professionals, students, senior citizens and organisations.',
                'content' => <<<'MD'
## Membership grades

| Grade | Membership | Who it is for |
| --- | --- | --- |
| DCIIP | Patron (Director's Council) | Appointed or nominated by the Chairman |
| EFIIP | Elite Fellow | Conferred on eminent professionals |
| HMIIP | Honorary Member | The highest distinction, for exceptional service only |
| FMIIP | Fellow Member | 10+ years of professional standing, or 5+ years as a member |
| MIIP | Member | Age 30+ with 5+ years in the profession (age 25+ and 3 years for IIP exam alumni) |
| AMIIP | Associate Member | Age 25+ with 3+ years of professional work (age 19+ for IIP exam alumni); no voting rights |
| CMIIP | Corporate Member | Firms and companies, through an authorised person; up to three nominees |
| IIIP | Institutional Member | Departments, labs, research groups and institutions; up to three nominees |
| SIIP | Society Member | Societies, through an authorised person; up to three nominees |
| SCIIP | Senior Citizen Member | Age 60+, approved by the Managing Committee |
| SSIIP | Super Senior Citizen Member | Age 80+, approved by the Managing Committee |
| VIIP | Volunteer Member | Age 19+, for voluntary service; any earnings go to the IIP fund |
| STIIP | Student Member | Age 18+, enrolled at a recognised institute; no voting rights |
| INIIP | International Member | Non-Indian citizens, age 30+ with 5+ years in the profession |
| NIIP | NRI Member | Indian citizens living abroad, age 30+ with 5+ years in the profession |

Use **Register** at the top of the page to apply.
MD,
            ],
            'how-it-works' => [
                'title' => 'How It Works',
                'excerpt' => 'From registration to recognition: how IIP helps professionals grow.',
                'content' => <<<'MD'
## 1. Register

Create your IIP profile as a professional, a student or an organisation.

![Register with IIP](https://iiprof.com/public/img/howpic1.jpg)

## 2. Get noticed by recruiters

Your profile, skills and certifications are visible to IIP’s talent-acquisition partners.

![Get noticed by recruiters](https://iiprof.com/public/img/howpic2.jpg)

## 3. Grow your career

Certification, guidance from industry experts and career-path support help you take the next step.

![Grow your career](https://iiprof.com/public/img/howpic3.jpg)

## 4. Grow with the community

Offer your services to other members, learn from them and grow your network.

![Grow with the community](https://iiprof.com/public/img/howpic4.jpg)
MD,
            ],
            'hunar-book-my-seat' => [
                'title' => 'Book My Seat',
                'excerpt' => 'Reserve your admission — from playschool to PhD, ITI and skill training — through IIP.',
                'content' => <<<'MD'
## Programmes

- **Schooling:** crèche / playschool, nursery, pre-primary, primary, secondary and higher secondary
- **Higher education:** undergraduate, master's, PhD, diploma, PG diploma, MBA, engineering, medical and law
- **Other:** ITI, polytechnic, competitive examinations, coaching, tuition, overseas education and skill training (paid or free)

## Batch sizes

1:1, 1:4, 1:8, 1:16 or 1:32 — classes start in about 3 to 120 days, depending on the batch size.

## Scholarships

Up to **50%**, based on your score in an online exam.

## Refund policy

If an institution does not accept your application, the admission fee is refunded in full within 15 days.

Use **Enquire** to book your seat.
MD,
            ],
            'contact-us' => [
                'title' => 'Contact Us',
                'excerpt' => 'Write to us or visit the Asia Pacific Head Office in Kolkata.',
                'content' => "## Asia Pacific Head Office\n\n152, S.P. Mukherjee Road, Abhishek Point Building, 5th Floor  \nKolkata 700026, West Bengal, India\n\n**Email:** [info@iiprof.com](mailto:info@iiprof.com)\n\nFor questions about membership, programmes or partnerships, use the **Enquire** form — our team will get back to you.",
            ],
            'bookings' => ['title' => 'Bookings', 'excerpt' => 'Travel, hotel and ticket bookings for members — coming soon.', 'content' => "## Coming soon\n\nTravel, hotel and ticket bookings for IIP members will open here shortly. Use **Enquire** to be notified."],
            'campaign' => ['title' => 'Campaigns', 'excerpt' => 'Fund-raising and community campaigns run with IIP.', 'content' => "## No campaign is running right now\n\nApproved campaigns will appear here. To start a campaign with IIP, send us a query."],
        ];
    }

    // ------------------------------------------------------------------
    // Import
    // ------------------------------------------------------------------

    public static function imported(): bool
    {
        return Setting::query()->where('key', self::MARKER)->exists();
    }

    /** Run the import once per installation (data migration + seeder call this). */
    public static function importOnce(): array
    {
        return self::imported() ? ['content' => 'already imported'] : self::import();
    }

    /**
     * @return array<string, string> block => what happened
     */
    public static function import(bool $force = false): array
    {
        $report = [];

        DB::transaction(function () use ($force, &$report) {
            $report['settings'] = self::importSettings($force);
            $report['pages'] = self::importPages($force);
            $report['menu'] = self::importMenu($force);
            $report['footer'] = self::importFooter($force);
            $report['announcements'] = self::replaceIf($force || self::starter(Announcement::class, 'text', [
                'Membership registration is open — join the Brain Bank of Global Talents.',
                'Scholarship Test: register now to qualify for IIP scholarships.',
                'Explore Hunar, Jeevika and Khazana — education, careers and finance in one place.',
            ]), Announcement::class, fn () => collect(self::announcements())->each(fn ($a) => Announcement::query()->create(['text' => $a[0], 'url' => $a[1]])));
            $report['hero slides'] = self::replaceIf($force || self::starter(HeroSlide::class, 'title', [
                'Accelerate Your Career Growth', 'Expand Community Outreach', 'Grow Your Network',
            ], noImages: true), HeroSlide::class, fn () => collect(self::heroSlides())->each(fn ($s) => HeroSlide::query()->create($s)));
            $report['highlights'] = self::replaceIf($force || ! Highlight::query()->exists(), Highlight::class, fn () => collect([
                ['Create Community', 'Bring professionals, institutions and organisations together around shared goals.', 'user-group'],
                ['Help to grow others as well grow yourself', 'Mentor, collaborate and share opportunities — growth that multiplies.', 'trending-up'],
                ['Upgrade knowledge & grow professionally', 'Courses, tests and programmes that keep your skills ahead of the curve.', 'academic-cap'],
            ])->each(fn ($h) => Highlight::query()->create(['title' => $h[0], 'description' => $h[1], 'icon' => $h[2]])));
            $report['events'] = self::importEvent($force);
            $report['gallery'] = self::replaceIf($force || ! GalleryItem::query()->exists(), GalleryItem::class,
                fn () => collect(self::gallery())->each(fn ($g) => GalleryItem::query()->create(['title' => $g[0], 'image' => $g[1]])));
            $report['team'] = self::replaceIf($force || ! TeamMember::query()->exists(), TeamMember::class,
                fn () => collect(self::team())->each(fn ($t) => TeamMember::query()->create(['group' => $t[0], 'name' => $t[1], 'role' => $t[2], 'photo' => $t[3]])));
            $report['testimonials'] = self::replaceIf($force || ! Testimonial::query()->exists(), Testimonial::class,
                fn () => collect(self::testimonials())->each(fn ($t) => Testimonial::query()->create(['name' => $t[0], 'video_url' => $t[1], 'photo' => $t[2]])));
            $report['homepage sections'] = self::importSections($force);
        });

        Setting::query()->updateOrCreate(['key' => self::MARKER], ['value' => self::VERSION.' '.now()->toDateTimeString()]);
        Cache::forget(SiteSettings::CACHE_KEY);

        return $report;
    }

    /** True when the table is empty or still holds exactly the given starter values. */
    private static function starter(string $model, string $column, array $values, bool $noImages = false): bool
    {
        $rows = $model::query()->get();
        if ($rows->isEmpty()) {
            return true;
        }

        $same = $rows->pluck($column)->sort()->values()->all() === collect($values)->sort()->values()->all();

        return $same && (! $noImages || $rows->every(fn ($row) => blank($row->image)));
    }

    private static function replaceIf(bool $replace, string $model, callable $create): string
    {
        if (! $replace) {
            return 'kept (edited in the admin)';
        }

        // Delete one by one so local image files are cleaned up too.
        $model::query()->get()->each->delete();
        $create();

        return 'imported';
    }

    private static function importSettings(bool $force): string
    {
        $shipped = [
            'site_name' => ['iiprof'], 'short_name' => [], 'tagline' => ['Built with Laravel, Livewire and React'],
            'meta_description' => ['International Institute of Professionals — a brain bank of global talents connecting learners, professionals, employers, financiers and healthcare providers.'],
            'address' => [self::STARTER_ADDRESS], 'footer_text' => [],
        ];

        $changed = 0;
        foreach (self::settings() as $key => $value) {
            $current = Setting::query()->where('key', $key)->value('value');
            if ($force || blank($current) || in_array($current, $shipped[$key] ?? [], true)) {
                Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
                $changed++;
            }
        }

        return $changed ? "imported ({$changed} values)" : 'kept (edited in the admin)';
    }

    private static function importPages(bool $force): string
    {
        // Starter pages from earlier versions move to the old iiprof.com addresses.
        foreach (['about' => 'about-us', 'contact' => 'contact-us'] as $old => $new) {
            $page = Page::query()->where('slug', $old)->first();
            if ($page && self::isStarterPage($page) && ! Page::query()->where('slug', $new)->exists()) {
                $page->update(['slug' => $new]);
            }
        }

        $created = $updated = 0;
        foreach (self::pages() as $slug => $data) {
            $page = Page::query()->where('slug', $slug)->first();
            $values = [
                'title' => $data['title'],
                'excerpt' => $data['excerpt'],
                'content' => $data['content'] !== '' ? $data['content'] : null,
                'status' => PageStatus::Published,
                'published_at' => $page?->published_at ?? now(),
            ] + (array_key_exists('image', $data) ? ['featured_image' => $data['image']] : []);

            if (! $page) {
                Page::query()->create(['slug' => $slug, 'show_in_menu' => false] + $values);
                $created++;
            } elseif ($force || self::isStarterPage($page)) {
                $page->update($values);
                $updated++;
            }
        }

        return "{$created} created, {$updated} updated";
    }

    private static function isStarterPage(Page $page): bool
    {
        $content = (string) $page->content;

        return str_starts_with($content, "## Our story\n\nTell visitors who you are.")
            || str_starts_with($content, "## Get in touch\n\nContact details shown in the footer")
            || str_starts_with($content, "## Brain Bank of Global Talents\n\nThe **International Institute of Professionals (IIP)** connects learners");
    }

    private static function importMenu(bool $force): string
    {
        $items = MenuItem::query()->get(['id', 'parent_id', 'label']);
        $top = $items->whereNull('parent_id')->pluck('label')->values()->all();
        $isStarter = $items->isEmpty() || ($items->count() === 57 && $top === ['Home', 'About Us', 'Hunar', 'Jeevika', 'Khazana', 'Contact']);

        if (! $force && ! $isStarter) {
            return 'kept (edited in the admin)';
        }

        MenuItem::query()->whereNull('parent_id')->get()->each->delete(); // children cascade
        MenuItem::query()->delete();

        $pages = Page::query()->get(['id', 'slug'])->keyBy(fn (Page $p) => $p->path());
        $count = 0;
        $create = function (array $nodes, ?int $parent) use (&$create, $pages, &$count) {
            foreach ($nodes as $i => $node) {
                [$label, $url, $children] = $node;
                $page = $url ? $pages->get($url) : null;
                $item = MenuItem::query()->create([
                    'parent_id' => $parent,
                    'label' => $label,
                    'url' => $page ? null : $url,
                    'page_id' => $page?->id,
                    'new_tab' => (bool) ($node[3] ?? false),
                    'sort_order' => $i + 1,
                ]);
                $count++;
                $create($children, $item->id);
            }
        };
        $create(self::menu(), null);

        return "imported ({$count} links)";
    }

    private static function importFooter(bool $force): string
    {
        $starterUrls = ['/about', '/careers', '/membership', '#enquiry', 'https://www.exin.com', '/dbc', 'https://www.skillindiadigital.gov.in',
            'https://nsdcindia.org', '/terms-and-conditions', '/privacy-policy', '/disclaimer-policy', '/cancellation-and-refund-policy'];
        $urls = DB::table('footer_links')->pluck('url')->sort()->values()->all();
        $isStarter = $urls === [] || $urls === collect($starterUrls)->sort()->values()->all();

        if (! $force && ! $isStarter) {
            return 'kept (edited in the admin)';
        }

        FooterColumn::query()->get()->each->delete();
        foreach (self::footer() as $title => $links) {
            $column = FooterColumn::query()->create(['title' => $title]);
            foreach ($links as $link) {
                $column->links()->create(['label' => $link[0], 'url' => $link[1], 'new_tab' => $link[2] ?? false]);
            }
        }

        return 'imported';
    }

    private static function importEvent(bool $force): string
    {
        $data = self::event();
        $existing = Event::query()->where('slug', $data['slug'])->first();
        $starter = $existing && blank($existing->image) && str_starts_with((string) $existing->description, "## About the test\n\nThe IIP Scholarship Test recognises talent");

        if ($existing && ! $force && ! $starter) {
            return 'kept (edited in the admin)';
        }

        $existing ? $existing->update($data) : Event::query()->create($data);

        return 'imported';
    }

    private static function importSections(bool $force): string
    {
        HomeSection::ensureAll();
        $done = [];

        foreach (self::sections() as $key => $values) {
            $section = HomeSection::query()->where('key', $key)->first();
            $starter = match ($key) {
                'hero' => in_array($section->button_url, [null, '/about'], true),
                'about' => blank($section->body) || str_starts_with((string) $section->body, 'The **International Institute of Professionals (IIP)** brings'),
                'contact' => blank($section->body) || str_starts_with((string) $section->body, 'Knowledge grows when it is shared.'),
                default => blank($section->title) && blank($section->eyebrow), // stats, highlights, events, gallery

            };

            if ($force || $starter) {
                $section->update($values);
                $done[] = $key;
            }
        }

        return $done ? 'updated: '.implode(', ', $done) : 'kept (edited in the admin)';
    }

    /**
     * A published page for every address in the menu and footer that has none yet, so each one gets
     * its own banner in Admin → Pages (title, image, description, button). Their text starts empty:
     * the website shows a landing page until text is added (or pulled from iiprof.com).
     *
     * @return int pages created
     */
    public static function ensureLinkPages(): int
    {
        $links = collect();
        foreach (MenuItem::query()->whereNotNull('url')->get(['label', 'url']) as $item) {
            $links->push([$item->url, $item->label]);
        }
        foreach (\App\Models\FooterLink::query()->get(['label', 'url']) as $link) {
            $links->push([$link->url, $link->label]);
        }

        $created = 0;
        foreach ($links->unique(0) as [$url, $label]) {
            $path = '/'.trim((string) parse_url($url, PHP_URL_PATH), '/');
            if (! str_starts_with($url, '/') || $path === '/' || str_starts_with($path, '/admin') || str_starts_with($path, '/api')) {
                continue;
            }

            $slug = strtolower(str_replace('/', '-', trim($path, '/')));
            $nested = substr_count($path, '/') > 1;
            $page = Page::query()->where('public_path', $path)->orWhere('slug', $slug)->first();

            if ($page) {
                if ($nested && ! $page->public_path && $page->slug === $slug) {
                    $page->forceFill(['public_path' => $path])->saveQuietly();
                }
                continue;
            }

            Page::query()->create([
                'title' => $label,
                'slug' => $slug,
                'public_path' => $nested ? $path : null,
                'status' => PageStatus::Published,
                'published_at' => now(),
                'show_in_menu' => false,
            ]);
            $created++;
        }

        return $created;
    }
}
