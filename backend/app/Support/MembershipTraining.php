<?php

namespace App\Support;

use App\Models\FeaturedMember;
use App\Models\HomeItem;
use App\Models\HomeSection;
use App\Models\Event;
use App\Models\MemberOrganisation;
use App\Models\MembershipFee;
use App\Models\Post;
use App\Models\Seminar;
use App\Models\Setting;
use App\Models\TrainingBatch;
use App\Models\TrainingProgramme;
use Illuminate\Support\Facades\DB;

/**
 * Starting content for the membership and training blocks (modelled on the membership and training sections of a
 * directors' institute: join + member community + member directory, why join, how to apply & fees, training
 * programmes with scheduled dates, types of training, benefits, faculty) and for the blocks added with them:
 * next event, past events, news & blogs, seminars, board members, corporate and institutional members.
 *
 * Texts and figures are IIP's own: the 15 grades and their fees are those of iiprof.com's registration form, the
 * objectives come from IIP's mission, the training benefits from Book My Seat, the patrons are the Director Council.
 * The training programmes are starter descriptions of IIP's training areas and the batch dates are SAMPLES — replace
 * them under Training in the admin. News posts are starter posts based on IIP's announcements, the seminar is a TEST
 * entry, the corporate and institutional members are SAMPLES, and the past events are the occasions of the gallery
 * photos (dated by the photos, approximately). Added once (marker `membership_training`); deleted items stay deleted.
 */
class MembershipTraining
{
    public const MARKER = 'membership_training';

    public const NEW_KEYS = [
        'next_event', 'membership_hub', 'membership_objectives', 'membership_apply', 'corporate_members', 'institutions',
        'training', 'training_formats', 'training_benefits', 'faculty', 'seminars', 'past_events', 'news', 'board',
    ];

    /** Full order on a homepage whose blocks are still in the order of the previous version. */
    public const DEFAULT_ORDER = [
        'hero', 'stats', 'next_event', 'pillars', 'about', 'membership_hub', 'highlights', 'membership_objectives', 'benefits',
        'membership', 'membership_apply', 'corporate_members', 'institutions', 'impact', 'training', 'training_formats',
        'training_benefits', 'faculty', 'programmes', 'seminars', 'events', 'past_events', 'news', 'board', 'gallery',
        'testimonials', 'partners', 'faq', 'join', 'contact',
    ];

    /** Where each new block goes on a homepage whose order was changed in the admin: [after|before, key]. */
    private const ANCHORS = [
        'next_event' => ['after', 'stats'],
        'membership_hub' => ['after', 'about'],
        'membership_objectives' => ['after', 'membership_hub'],
        'membership_apply' => ['after', 'membership'],
        'corporate_members' => ['after', 'membership_apply'],
        'institutions' => ['after', 'corporate_members'],
        'training' => ['before', 'programmes'],
        'training_formats' => ['after', 'training'],
        'training_benefits' => ['after', 'training_formats'],
        'faculty' => ['after', 'training_benefits'],
        'seminars' => ['before', 'events'],
        'past_events' => ['after', 'events'],
        'news' => ['after', 'past_events'],
        'board' => ['after', 'news'],
    ];

    private const MEDIA = '/media/menu/';

    /** @return array<string, array<string, ?string>> */
    public static function sections(): array
    {
        $m = self::MEDIA;

        return [
            'membership_hub' => [
                'eyebrow' => 'Membership', 'title' => 'Connect with professionals', 'title_accent' => 'of every field',
                'subtitle' => 'An opportunity to learn from experts, offer your services and grow with a community that spans every profession.',
                'body' => "Join the **International Institute of Professionals**, a not-for-profit brain bank founded in 2012. Choose one of 15 membership grades — for a year or for life — and take part in the community, its events and training, and the Hunar, Jeevika and Khazana programmes.",
                'image' => $m.'jeevika.webp',
                'button_text' => 'Become a member', 'button_url' => '#register',
            ],
            'membership_objectives' => [
                'eyebrow' => 'Why join', 'title' => 'Membership with a', 'title_accent' => 'purpose',
                'subtitle' => 'IIP brings professionals of all fields onto one platform for mutual understanding and benefit.',
                'body' => 'Members belong to a cohesive fraternity — from budding talent to senior experts — that up-skills people, opens up income opportunities and works for social and environmental good.',
                'image' => $m.'more.webp',
            ],
            'membership_apply' => [
                'eyebrow' => 'How to apply', 'title' => 'Become a member in', 'title_accent' => 'four steps',
                'subtitle' => 'Choose your grade, register online and pay the fee for one year or for life.',
                'body' => 'Fees in Indian rupees. Patrons are appointed or nominated by the Chairman; honorary membership is conferred by IIP.',
                'button_text' => 'Register now', 'button_url' => '#register',
            ],
            'training' => [
                'eyebrow' => 'Training & development', 'title' => 'Programmes that', 'title_accent' => 'build careers',
                'subtitle' => 'Skills, employability, leadership and entrepreneurship programmes — in open batches, at your organisation or online.',
                'button_text' => 'All programmes', 'button_url' => '/training',
            ],
            'training_formats' => [
                'eyebrow' => 'Types of training', 'title' => 'Learn the way that', 'title_accent' => 'suits you',
                'subtitle' => 'Online, offline or both — in batches from one-to-one up to 32 participants.',
            ],
            'training_benefits' => [
                'eyebrow' => 'Why train with IIP', 'title' => 'Learning that leads to', 'title_accent' => 'opportunity',
                'subtitle' => 'What every IIP learner gets, from admission to placement.',
                'image' => $m.'hunar.webp',
                'button_text' => 'Book my seat', 'button_url' => '/hunar/book-my-seat',
            ],
            'faculty' => [
                'eyebrow' => 'Faculty', 'title' => 'Learn from', 'title_accent' => 'experienced professionals',
                'subtitle' => 'Trainers and industry experts of the IIP network.',
            ],
            'next_event' => [
                'eyebrow' => 'Next event', 'title' => 'Don’t miss', 'title_accent' => 'what’s next',
                'button_text' => 'All events', 'button_url' => '/events',
            ],
            'past_events' => [
                'eyebrow' => 'Past events', 'title' => 'Moments from', 'title_accent' => 'our events',
                'subtitle' => 'Meetings, accreditations and seminars — with photos and videos.',
                'button_text' => 'View the gallery', 'button_url' => '/view-gallery',
            ],
            'news' => [
                'eyebrow' => 'News & blogs', 'title' => 'Latest from', 'title_accent' => 'IIP',
                'subtitle' => 'Announcements, news and articles for professionals.',
                'button_text' => 'All news & blogs', 'button_url' => '/news',
            ],
            'seminars' => [
                'eyebrow' => 'Seminars', 'title' => 'Upcoming', 'title_accent' => 'seminars',
            ],
            'board' => [
                'eyebrow' => 'Leadership', 'title' => 'Board', 'title_accent' => 'members',
                'subtitle' => 'The patrons and governing members who guide the institute.',
                'button_text' => 'Meet the management', 'button_url' => '/management/director-council',
            ],
            'corporate_members' => [
                'eyebrow' => 'Corporate members', 'title' => 'Organisations that', 'title_accent' => 'grow with IIP',
                'subtitle' => 'Companies and firms in the IIP network.',
                'button_text' => 'Become a corporate member', 'button_url' => '#register',
            ],
            'institutions' => [
                'eyebrow' => 'Institutional members', 'title' => 'Universities, colleges &', 'title_accent' => 'institutes',
                'subtitle' => 'Educational institutions that work with IIP.',
                'button_text' => 'Partner with IIP', 'button_url' => '#enquiry',
            ],
        ];
    }

    /** @return list<array<string, mixed>> starter posts based on IIP's announcements */
    public static function posts(): array
    {
        $m = self::MEDIA;

        return [
            [
                'type' => 'news', 'is_featured' => true, 'published_on' => '2026-10-20', 'author' => 'IIP team', 'image' => $m.'about-us.webp',
                'title' => 'Membership registration is open',
                'excerpt' => 'Join the Brain Bank of Global Talents: 15 membership grades for students, professionals, senior citizens, NRIs and organisations.',
                'body' => "Registration for IIP membership is open.\n\n## Fifteen grades\n\nFrom **Student Member** (₹250 a year) to **Member**, **Fellow Member** and **Corporate Member**, there is a grade for every stage of your career — for one year or for life. Honorary and volunteer membership is free.\n\n## How to join\n\n1. Choose your grade on the [Membership](/membership) page.\n2. Click **Register** and fill in the form.\n3. Pay the fee and your profile is activated after review.",
            ],
            [
                'type' => 'news', 'published_on' => '2026-10-14', 'author' => 'IIP team', 'image' => $m.'hunar-book-my-seat.webp',
                'title' => 'Book My Seat: scholarships of up to 50%',
                'excerpt' => 'Reserve your admission through Hunar — from playschool to PhD — and earn a scholarship of up to 50% in the online examination.',
                'body' => "With **Book My Seat**, IIP reserves your admission at a partner institution — schooling, higher education, ITI, polytechnic, coaching and skill training.\n\n- Scholarships of up to **50%** based on an online examination\n- Education loans and easy instalments\n- Batch sizes from 1:1 to 1:32\n\n[Book your seat](/hunar/book-my-seat)",
            ],
            [
                'type' => 'blog', 'published_on' => '2026-10-02', 'author' => 'IIP team', 'image' => $m.'community.webp',
                'title' => 'Five ways to get the most out of your IIP membership',
                'excerpt' => 'Your membership opens doors across professions. Here is how to use it from the first week.',
                'body' => "IIP brings professionals of every field onto one platform. These five habits help new members get value quickly.\n\n## 1. Complete your profile\n\nA complete profile — profession, skills and industry category — makes you easy to find for other members and recruiters.\n\n## 2. Join an event\n\nSeminars and member sessions are the fastest way to meet people outside your own profession.\n\n## 3. Learn something new\n\nPick a programme from the [training calendar](/training#calendar) and keep your skills current.\n\n## 4. Offer your services\n\nConsultants, trainers and service providers can reach the whole community through IIP.\n\n## 5. Give back\n\nVolunteer, mentor or support a CSR project through Philanthropy.",
            ],
            [
                'type' => 'article', 'published_on' => '2026-09-18', 'author' => 'IIP team', 'image' => $m.'hunar.webp',
                'title' => 'Why up-skilling matters in every profession',
                'excerpt' => 'Skills age faster than ever. Continuous learning is what keeps professionals — and organisations — ready for change.',
                'body' => "Every profession changes: new tools, new rules, new expectations from clients and employers.\n\n## Skill, re-skill, up-skill\n\nIIP's approach is to help the unskilled become skilled, and the skilled become **multiskilled** — so that people can take up more than one source of income.\n\n## Learning that leads somewhere\n\nThe best training ends in an opportunity: a job, a promotion, a new client or a business of your own. That is why IIP links its programmes to placement, entrepreneurship support and the member community.\n\n> The measure of success is not only how much you earn, but how you grow in the process.",
            ],
        ];
    }

    /** @return list<array{0: string, 1: string, 2: string, 3: string}> kind, name, category, city — SAMPLES */
    public static function organisations(): array
    {
        return [
            ['corporate', 'Sample Technologies Pvt. Ltd.', 'IT & ITeS', 'Kolkata'],
            ['corporate', 'Sample Finance Ltd.', 'Finance', 'Mumbai'],
            ['corporate', 'Sample Healthcare LLP', 'Healthcare', 'Pune'],
            ['corporate', 'Sample Manufacturing Co.', 'Manufacturing', 'Chennai'],
            ['institutional', 'Sample University', 'University', 'Kolkata'],
            ['institutional', 'Sample College of Engineering', 'College', 'Durgapur'],
            ['institutional', 'Sample Public School', 'School', 'Howrah'],
            ['institutional', 'Sample Institute of Management', 'Institute', 'Bengaluru'],
            ['institutional', 'Sample Polytechnic', 'Polytechnic / ITI', 'Siliguri'],
            ['institutional', 'Sample Nursing College', 'College', 'Kolkata'],
        ];
    }

    /** @return array<string, list<array<string, ?string>>> HomeItem lists per block */
    public static function items(): array
    {
        return [
            'membership_hub' => [
                ['value' => '15', 'title' => 'Membership grades', 'description' => 'For students, professionals, senior citizens, NRIs and organisations.'],
                ['value' => '₹250', 'title' => 'Student membership', 'description' => 'A year of membership for students aged 18 and over.'],
                ['value' => 'Free', 'title' => 'Honorary & volunteer', 'description' => 'No fee for honorary members and for volunteers who serve the community.'],
            ],
            'membership_objectives' => [
                ['title' => 'One platform for every profession', 'description' => 'Professionals of all fields together, for better mutual understanding and benefit.'],
                ['title' => 'Knowledge and skills', 'description' => 'Enrich the knowledge, skills, aptitude and attitude that every profession is built on.'],
                ['title' => 'Cross-pollination', 'description' => 'Learn from other professions — and help them grow in return.'],
                ['title' => 'Good governance', 'description' => 'Transparency, accountability and effective leadership in organisations.'],
                ['title' => 'Careers and income', 'description' => 'Career advancement, talent acquisition and multiple income opportunities.'],
                ['title' => 'Research and innovation', 'description' => 'Research, development, innovation and incubation across industries.'],
                ['title' => 'Serve the community', 'description' => 'Social and environmental upliftment, and a part in nation building.'],
            ],
            'membership_apply' => [
                ['title' => 'Choose your grade', 'icon' => 'identification', 'description' => 'Check who each of the 15 grades is for on the Membership page.'],
                ['title' => 'Register online', 'icon' => 'user', 'description' => 'Fill in the form with your profession, organisation and industry category.'],
                ['title' => 'Pay the fee', 'icon' => 'banknotes', 'description' => 'For one year or for life time — see the fee of each grade in the table.'],
                ['title' => 'Join the community', 'icon' => 'check', 'description' => 'Your profile is activated once the IIP team has confirmed your application.'],
            ],
            'training_formats' => [
                ['title' => 'Open batches', 'subtitle' => 'At IIP centres', 'icon' => 'user-group', 'button_text' => 'See the calendar', 'button_url' => '/training#calendar',
                    'description' => 'Join a scheduled batch with learners from other organisations, with batch sizes from 1:1 to 1:32.'],
                ['title' => 'In-company training', 'subtitle' => 'At your organisation', 'icon' => 'building', 'button_text' => 'Request a proposal', 'button_url' => '#enquiry',
                    'description' => 'Programmes tailored to your team, delivered at your premises or online.'],
                ['title' => 'Online learning', 'subtitle' => 'Live & virtual', 'icon' => 'globe', 'button_text' => 'Browse programmes', 'button_url' => '/training',
                    'description' => 'Live virtual classes and on-the-job training through embedded technology.'],
            ],
            'training_benefits' => [
                ['title' => 'Industry-relevant education', 'icon' => 'academic-cap', 'description' => 'Value-based, quality education that industry asks for, with global university alliances.'],
                ['title' => 'Placement assistance', 'icon' => 'briefcase', 'description' => 'Placement and post-placement support, on-the-job training, internships and apprenticeships.'],
                ['title' => 'Scholarships', 'icon' => 'gift', 'description' => 'Scholarships of up to 50% based on an online examination, education loans and easy instalments.'],
                ['title' => 'Career counselling', 'icon' => 'chart', 'description' => 'Psychometric competency mapping and counselling to choose the right career.'],
                ['title' => 'Entrepreneurship support', 'icon' => 'light-bulb', 'description' => 'Incubation for new ideas and 360-degree handholding to start your own enterprise.'],
                ['title' => 'Flexible batches', 'icon' => 'clock', 'description' => 'Online, offline or both, with batch sizes from 1:1 to 1:32.'],
            ],
        ];
    }

    /**
     * Fees of iiprof.com's registration form (grade, code, 1 year, life time) — rupees.
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string, 4?: string}>
     */
    public static function fees(): array
    {
        return [
            ['Patrons (Director’s Council)', 'DCIIP', '5000', '50000', 'Appointed or nominated by the Chairman'],
            ['Elite Fellow', 'EFIIP', '2500', '25000', 'Conferred by the Directors’ Council'],
            ['Honorary Member', 'HMIIP', 'Free', 'Free', 'Conferred by IIP'],
            ['Fellow Member', 'FMIIP', '2000', '20000'],
            ['Member', 'MIIP', '1500', '15000'],
            ['Associate Member', 'AMIIP', '1000', '10000'],
            ['Corporate Member', 'CMIIP', '3000', '30000', 'Up to three nominees'],
            ['Institutional Member', 'IIIP', '3000', '30000', 'Up to three nominees'],
            ['Society Member', 'SIIP', '3000', '30000', 'Up to three nominees'],
            ['Senior Citizen Member', 'SCIIP', '750', '7500'],
            ['Super Senior Citizen Member', 'SSIIP', 'On request', 'On request'],
            ['Volunteer Member', 'VIIP', 'Free', 'Free'],
            ['Student Member', 'STIIP', '250', null, 'One year at a time'],
            ['International Member', 'INIIP', '4000', '40000'],
            ['NRI Member', 'NIIP', '3500', '35000'],
        ];
    }

    /** @return list<array<string, mixed>> starter programmes, each with sample batches */
    public static function programmes(): array
    {
        $m = self::MEDIA;

        return [
            [
                'title' => 'Skill Development & Vocational Training', 'slug' => 'skill-development', 'type' => 'open', 'is_featured' => true,
                'tagline' => 'From unskilled to skilled — and multiskilled', 'duration' => '4 to 12 weeks', 'format' => 'Classroom · Online', 'fee' => 'Fee-based or free',
                'image' => $m.'jeevika-jobs.webp',
                'summary' => 'Hands-on vocational and professional skill training that turns unskilled and semi-skilled learners into skilled, employable professionals.',
                'description' => "Skill, re-skill, up-skill and multi-skill training led by experienced professionals of the IIP network.\n\nTraining is online, offline or both, in batches from one-to-one to 32 learners, and can be fee-based or free.",
                'audience' => "- School and college leavers\n- Unskilled and semi-skilled workers\n- Professionals who want to re-skill",
                'outcomes' => "- Job-ready skills that industry asks for\n- Skill-competency mapping and grading\n- Placement and post-placement assistance",
                'modules' => "Skill-competency mapping\nCore trade and technical skills\nDigital literacy\nWorkplace safety and practices\nOn-the-job training\nAssessment and grading",
                'certificate' => 'An IIP certificate of completion, with your grade recorded in the talent register.',
                'button_text' => 'Register interest', 'button_url' => '#enquiry',
                'batches' => [
                    ['batch_code' => 'SD-01', 'format' => 'classroom', 'location' => 'Kolkata', 'starts_on' => '2026-11-16', 'ends_on' => '2026-12-11', 'schedule' => 'Mon – Fri, 10 am – 1 pm'],
                    ['batch_code' => 'SD-02', 'format' => 'online', 'location' => 'Online', 'starts_on' => '2027-01-11', 'ends_on' => '2027-02-05', 'schedule' => 'Mon, Wed & Fri, 6 – 8 pm'],
                ],
            ],
            [
                'title' => 'Soft Skills & Employability', 'slug' => 'soft-skills-employability', 'type' => 'open',
                'tagline' => 'Get interview-ready and workplace-ready', 'duration' => '2 days', 'format' => 'Classroom', 'fee' => 'On request',
                'image' => $m.'more-contact-us.webp',
                'summary' => 'Communication, presentation and interview skills, teamwork and workplace etiquette for students and young professionals.',
                'description' => 'A practical workshop on the soft skills employers look for, with role plays, mock interviews and personal feedback.',
                'audience' => "- Final-year students and fresh graduates\n- Job seekers returning to work\n- Early-career professionals",
                'outcomes' => "- Confident communication and presentation\n- A stronger CV and interview technique\n- Workplace etiquette and teamwork",
                'modules' => "Communication and listening\nPresentation skills\nCV writing\nMock interviews\nTeamwork and workplace etiquette\nTime and stress management",
                'certificate' => 'An IIP certificate of participation.',
                'button_text' => 'Register interest', 'button_url' => '#enquiry',
                'batches' => [
                    ['batch_code' => 'SSE-01', 'format' => 'classroom', 'location' => 'Kolkata', 'starts_on' => '2026-11-28', 'ends_on' => '2026-11-29', 'schedule' => 'Sat & Sun, 10 am – 5 pm'],
                ],
            ],
            [
                'title' => 'Leadership & Good Governance', 'slug' => 'leadership-good-governance', 'type' => 'in-house',
                'tagline' => 'For managers, directors and team leaders', 'duration' => '2 days', 'format' => 'Hybrid · In-company', 'fee' => 'On request',
                'image' => $m.'consultancy.webp',
                'summary' => 'Corporate leadership, good governance, transparency and accountability — for professionals who lead teams and organisations.',
                'description' => 'A programme on effective leadership and good governance, built on IIP’s mission of transparency, accountability and sustainable growth. Available as an open batch or at your organisation.',
                'audience' => "- Managers and team leaders\n- Directors and board members\n- Leaders of NGOs, co-operatives and MSMEs",
                'outcomes' => "- A framework for effective leadership\n- Good-governance practices for your organisation\n- Regulatory compliance and professional ethics",
                'modules' => "Leadership styles and self-awareness\nGood governance and ethics\nRegulatory compliance\nStrategic decision-making\nLeading change\nCase studies and group work",
                'certificate' => 'An IIP certificate of participation.',
                'button_text' => 'Request a proposal', 'button_url' => '#enquiry',
                'batches' => [
                    ['batch_code' => 'LG-01', 'format' => 'hybrid', 'location' => 'Kolkata', 'starts_on' => '2026-12-05', 'ends_on' => '2026-12-06', 'schedule' => 'Sat & Sun, 10 am – 4 pm'],
                ],
            ],
            [
                'title' => 'Entrepreneurship Development Programme (EDP)', 'slug' => 'entrepreneurship-development', 'type' => 'open', 'is_featured' => true,
                'tagline' => 'Start and grow your own enterprise', 'duration' => '3 days', 'format' => 'Classroom', 'fee' => 'On request',
                'image' => $m.'jeevika-entrepreneur.webp',
                'summary' => 'From idea to enterprise: business planning, finance, marketing and 360-degree handholding — with access to funding through Khazana.',
                'description' => 'An entrepreneurship development programme for self-employment and social entrepreneurship based on local resources, with incubation and handholding support after the programme.',
                'audience' => "- Aspiring entrepreneurs\n- SHG and JLG members\n- MSME owners who want to grow",
                'outcomes' => "- A business plan for your idea\n- Know-how on finance, subsidies and loans\n- Handholding and incubation support",
                'modules' => "Finding and testing an idea\nBusiness planning\nFinance, subsidies and loans\nMarketing and selling\nCompliance and registration\nPitching to lenders and investors",
                'certificate' => 'An IIP EDP certificate.',
                'button_text' => 'Register interest', 'button_url' => '#enquiry',
                'batches' => [
                    ['batch_code' => 'EDP-01', 'format' => 'classroom', 'location' => 'Kolkata', 'starts_on' => '2027-01-16', 'ends_on' => '2027-01-18', 'schedule' => '10 am – 5 pm'],
                ],
            ],
            [
                'title' => 'Business Analysis & Financial Engineering', 'slug' => 'business-analysis-financial-engineering', 'type' => 'in-house',
                'tagline' => 'For finance and strategy teams', 'duration' => 'Tailored', 'format' => 'In-company · Online', 'fee' => 'On request',
                'image' => $m.'khazana-fund-manager.webp',
                'summary' => 'Business analysis, financial engineering and commercial architecture, delivered for your team by IIP consultants.',
                'description' => 'A tailored programme from IIP’s consultancy practice, for organisations that want their teams to analyse, structure and finance business decisions better.',
                'audience' => "- Finance and accounts teams\n- Strategy and planning teams\n- Founders and CFOs",
                'outcomes' => "- Sharper business analysis\n- Better-structured financing\n- Practical tools for your own cases",
                'modules' => "Business analysis\nFinancial statements and ratios\nProject appraisal\nFinancial engineering and structuring\nRisk management\nYour own case studies",
                'certificate' => 'An IIP certificate of participation.',
                'button_text' => 'Request a proposal', 'button_url' => '#enquiry',
                'batches' => [],
            ],
            [
                'title' => 'Interior Design', 'slug' => 'interior-design', 'type' => 'online',
                'tagline' => 'With the IIP scholarship test', 'duration' => '12 weeks', 'format' => 'Online · Classroom', 'fee' => 'Scholarship available',
                'image' => $m.'hunar-world-edu.webp',
                'summary' => 'Learn interior design from practising designers — and score well in the IIP scholarship test to get a scholarship on the course fee.',
                'description' => "Interior design courses run with IIP's design partners. Take the IIP scholarship test before you enrol: the better your score, the higher your scholarship.",
                'audience' => "- Students who want a career in design\n- Working professionals changing careers\n- Home-business owners",
                'outcomes' => "- A portfolio of design projects\n- Industry software and drawing skills\n- Guidance from practising designers",
                'modules' => "Design principles\nSpace planning\nMaterials and finishes\nLighting and colour\nDesign software\nPortfolio project",
                'certificate' => 'A course certificate on completion.',
                'button_text' => 'Take the scholarship test', 'button_url' => '/events/scholarship-test',
                'batches' => [
                    ['batch_code' => 'ID-01', 'format' => 'online', 'location' => 'Online', 'starts_on' => '2026-12-14', 'ends_on' => '2027-03-12', 'schedule' => 'Weekends'],
                ],
            ],
        ];
    }

    public static function seeded(): bool
    {
        return filled(Setting::query()->where('key', self::MARKER)->value('value'));
    }

    /** @return list<string> what got content */
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
                if ($section && blank($section->title) && blank($section->eyebrow)) {
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

            if (! MembershipFee::query()->exists()) {
                foreach (self::fees() as $i => $fee) {
                    MembershipFee::query()->create([
                        'grade' => $fee[0], 'code' => $fee[1], 'annual_fee' => $fee[2], 'lifetime_fee' => $fee[3],
                        'note' => $fee[4] ?? null, 'sort_order' => $i + 1, 'is_active' => true,
                    ]);
                }
                $done[] = 'membership fees';
            }

            if (! FeaturedMember::query()->exists()) {
                foreach (IiprofContent::team() as $i => [$group, $name, $role, $photo]) {
                    if ($group === 'director-council') {
                        FeaturedMember::query()->create(['category' => 'patrons', 'name' => $name, 'designation' => $role, 'photo' => $photo, 'url' => '/management/director-council', 'sort_order' => $i + 1]);
                    }
                }
                $done[] = 'member directory';
            }

            if (! TrainingProgramme::query()->exists()) {
                foreach (self::programmes() as $i => $data) {
                    $batches = $data['batches'];
                    unset($data['batches']);
                    $programme = TrainingProgramme::query()->create([...$data, 'sort_order' => $i + 1, 'is_active' => true]);
                    foreach ($batches as $batch) {
                        TrainingBatch::query()->create([...$batch, 'training_programme_id' => $programme->id]);
                    }
                }
                $done[] = 'training programmes';
            }

            if (! Post::query()->exists()) {
                foreach (self::posts() as $post) {
                    Post::query()->create($post);
                }
                $done[] = 'news';
            }

            if (! Seminar::query()->exists()) {
                Seminar::query()->create([
                    'title' => 'Test seminar: Professional growth in a changing economy',
                    'slug' => 'test-seminar',
                    'summary' => 'A test entry to check how seminars look on the website — edit or delete it under News & seminars → Seminars.',
                    'description' => "## About the seminar\n\nThis is a **test seminar** with a featured image, a destination link and a rich-text description.\n\n### What you will hear about\n\n- Careers that grow across professions\n- Skills employers will ask for next\n- Funding and support for new ventures\n\n> Replace this text with the real seminar, or delete it.\n\n[Send an enquiry](#enquiry)",
                    'image' => self::MEDIA.'more-events.webp',
                    'link' => '#enquiry', 'button_text' => 'Reserve a seat',
                    'starts_on' => '2026-12-19', 'location' => 'Kolkata', 'is_active' => true,
                ]);
                $done[] = 'test seminar';
            }

            if (! MemberOrganisation::query()->exists()) {
                foreach (self::organisations() as $i => [$kind, $name, $category, $city]) {
                    MemberOrganisation::query()->create([
                        'kind' => $kind, 'name' => $name, 'category' => $category, 'city' => $city,
                        'description' => 'Sample entry — replace or delete it under Membership → '.($kind === 'corporate' ? 'Corporate' : 'Institutional').' members.',
                        'grade' => $kind === 'corporate' ? 'CMIIP' : 'IIIP', 'sort_order' => $i + 1,
                    ]);
                }
                $done[] = 'sample organisations';
            }

            // Past events: the occasions of the gallery photos (dated by the photo uploads — check the dates).
            if (! Event::query()->whereDate('starts_on', '<', now()->toDateString())->exists()) {
                $seminars = 0;
                foreach (IiprofContent::gallery() as [$title, $url]) {
                    if (! preg_match('~gallery_(\d{10})~', $url, $m)) {
                        continue;
                    }
                    $date = \Illuminate\Support\Carbon::createFromTimestamp((int) $m[1], 'Asia/Kolkata')->startOfDay();
                    $name = $title === 'Seminar' ? 'IIP Seminar, '.$date->format('F Y') : $title;
                    $summary = match (true) {
                        str_starts_with($title, 'Meeting') => 'IIP met representatives of the MSME sector and international sports personality Pradipta Kumar Roy.',
                        str_contains($title, 'Accreditation') => 'Accreditation of Pradipta Kumar Roy, member of the IIP Director Council.',
                        default => 'An IIP seminar for members and professionals.',
                    };
                    Event::query()->create(['title' => $name, 'starts_on' => $date->toDateString(), 'location' => 'Kolkata', 'summary' => $summary,
                        'image' => $url, 'media_url' => '/view-gallery', 'is_active' => true, 'sort_order' => 900 + (++$seminars)]);
                }
                $done[] = 'past events';
            }

            self::placeNewBlocks();
            Setting::query()->updateOrCreate(['key' => self::MARKER], ['value' => now()->toIso8601String()]);
        });

        return $done;
    }

    /**
     * On a homepage still in the previous default order: the full new order. Otherwise each new block is put
     * next to its anchor and the order set in the admin is kept.
     */
    private static function placeNewBlocks(): void
    {
        $current = HomeSection::query()->ordered()->pluck('key')->all();
        $old = array_values(array_diff($current, self::NEW_KEYS));

        if ($old === HomeBlocks::DEFAULT_ORDER) {
            $order = array_values(array_unique([...self::DEFAULT_ORDER, ...$current]));
        } else {
            $order = $old;
            foreach (self::NEW_KEYS as $key) {
                if (! in_array($key, $current, true)) {
                    continue;
                }
                [$where, $anchor] = self::ANCHORS[$key];
                $at = array_search($anchor, $order, true);
                if ($at === false) {
                    $contact = array_search('contact', $order, true);
                    $at = $contact === false ? count($order) : $contact;
                } elseif ($where === 'after') {
                    $at++;
                }
                array_splice($order, $at, 0, [$key]);
            }
        }

        foreach (HomeSection::query()->get() as $section) {
            $position = array_search($section->key, $order, true);
            $sort = ($position === false ? count($order) + $section->sort_order : $position) + 1;
            if ($section->sort_order !== $sort) {
                $section->forceFill(['sort_order' => $sort])->saveQuietly();
            }
        }
    }
}
