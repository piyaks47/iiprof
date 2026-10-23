<?php

namespace Tests\Feature;

use App\Livewire\Admin\Pages\Edit;
use App\Models\Page;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\Import\HtmlToMarkdown;
use App\Support\Import\PageImporter;
use Database\Seeders\DatabaseSeeder;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/** Fixtures copy the page structure of iiprof.com (Bootstrap layout) with placeholder text. */
class PageImporterTest extends TestCase
{
    use RefreshDatabase;

    private function page(string $title, string $body, string $after = ''): string
    {
        return <<<HTML
<!doctype html><html><head><title>International Institute of Professionals - Home</title><script>var x = 1;</script></head>
<body>
<div class="preloader"><div class="preloader-inner">Loading</div></div>
<nav id="mainNav" class="navbar"><a href="/">Home</a></nav>
<div class="page-title-area"><div class="d-table"><div class="d-table-cell"><h1>{$title}</h1></div></div></div>
<section class="cms-area section-padding"><div class="container">{$body}</div></section>
{$after}
<section class="footer-area"><p>Footer text</p></section>
<div class="left-fixed-inquery-form"><form id="inquery-form"><input name="email"><button>Send</button></form></div>
</body></html>
HTML;
    }

    private function markdown(string $html, string $base = 'https://iiprof.com/about-us'): string
    {
        $doc = new DOMDocument;
        @$doc->loadHTML('<?xml encoding="UTF-8"><div id="root">'.$html.'</div>');

        return (new HtmlToMarkdown($base))->convert($doc->getElementById('root'));
    }

    public function test_html_becomes_clean_markdown(): void
    {
        $md = $this->markdown(<<<'HTML'
<div class="row"><div class="col-md-12"><div class="details-cont">
  <div class="about-main-banner"><img class="animation-bounce" src="/public/img/imp-3.jpg" alt="About"></div>
  <h2>Know <span class="color-text">IIP</span></h2>
  <p>We are a <b> brain bank </b> of professionals.<br>Founded in 2012.</p>
  <p><i class="fa fa-check"></i> Learn <em>and</em> grow — see <a href="/how-it-works">how it works</a> or <a href="javascript:void(0)">click</a>.</p>
  <ul><li>First</li><li><strong>Second</strong> item<ul><li>Nested</li></ul></li><li></li></ul>
  <ol><li>One</li><li>Two</li></ol>
  <table><tr><th>Plan</th><th>Fee</th></tr><tr><td>Basic</td><td>₹ 500</td></tr></table>
  <img src="public/uploads/gallery/original/photo one.jpg">
  <div class="modal"><p>Hidden pop-up</p></div>
  <form><input name="q"><button>Go</button></form>
  <script>alert(1)</script>
  <p>Price * 2 = [x]</p>
</div></div></div>
HTML);

        $expected = <<<'MD'
![About](https://iiprof.com/public/img/imp-3.jpg)

## Know IIP

We are a **brain bank** of professionals.<br>Founded in 2012.

Learn *and* grow — see [how it works](https://iiprof.com/how-it-works) or click.

- First
- **Second** item
   - Nested

1. One
2. Two

| Plan | Fee |
| --- | --- |
| Basic | ₹ 500 |

![](https://iiprof.com/public/uploads/gallery/original/photo%20one.jpg)

Price \* 2 = \[x\]
MD;
        $this->assertSame(str_replace('<br>', "  \n", $expected), $md);
    }

    public function test_extracts_title_and_body_without_forms_or_popups(): void
    {
        $importer = new PageImporter;
        $html = $this->page('Contact', '<div class="row"><div class="col-lg-7"><h2>Get In <span class="color-text">Touch</span></h2><p>Write to us.</p>'
            .'<form class="contact-form"><input name="name"><div class="controls"><button class="button">Send Message</button></div></form></div>'
            .'<div class="col-lg-5"><div class="contact-info"><ul><li><div><span>info@example.org</span></div></li></ul></div></div></div>'
            .'<div class="mt-5"><div class="text-white"><a href="javascript:void(0)"></a></div></div>',
            '<div class="modal fade"><div class="modal-body"><form><input></form></div></div>');

        $data = $importer->extract($html, 'https://iiprof.com/contact-us');

        $this->assertSame('Contact', $data['title']);
        $this->assertSame("## Get In Touch\n\nWrite to us.\n\n- info@example.org", $data['content']);
        $this->assertSame([], $data['team']);
    }

    public function test_reads_people_with_biographies_and_testimonials(): void
    {
        $importer = new PageImporter;
        $bio = htmlspecialchars('<p>Twenty years in <b>project management</b>.</p><p>Mentor.</p>');
        $team = $importer->extract($this->page('Director Council', <<<HTML
<div class="row"><div class="col-lg-3 col-md-6 wow fadeInUp"><div class="single-team-box">
  <div class="team-image"><img src="https://iiprof.com/public/uploads/team/thumb/Mr. Chandan Roy__1679908912.jpg"></div>
  <div class="team-info"><div class="overlay-text"><h3>Mr. Chandan Roy</h3><span class="mt-1">Project Management Consultant</span>
  <div class="text-center"><button type="button" class="team-details-btn button" data-name="Mr. Chandan Roy" data-details="{$bio}">View Details</button></div></div></div>
</div></div></div>
HTML), 'https://iiprof.com/management/Director-Council');

        $this->assertSame('', $team['content']);
        $this->assertSame([[
            'name' => 'Mr. Chandan Roy',
            'role' => 'Project Management Consultant',
            'photo' => 'https://iiprof.com/public/uploads/team/thumb/Mr.%20Chandan%20Roy__1679908912.jpg',
            'bio' => "Twenty years in **project management**.\n\nMentor.",
        ]], $team['team']);

        $html = str_replace('<section class="cms-area section-padding"><div class="container">', '<section class="alumini-area"><div class="container">', $this->page('Testimonials', <<<'HTML'
<div class="alumini-item owl-carousel"><div class="row"><div class="col-md-5"><figure><img src="https://iiprof.com/public/uploads/testimonials/original/Ayushi More_1.jpg"></figure><div class="alumini-name">Ayushi More</div></div>
<div class="col-md-7"><div class="alumini-content"><div class="alumini-description mb-4"><p class="pt-md-4">A placeholder quote.</p></div></div></div></div></div>
<div class="alumini-item owl-carousel"><div class="row"><div class="col-md-5"><div class="alumini-name">Taufeek Ahmad</div></div>
<div class="col-md-7"><div class="alumini-description"><iframe src="https://www.youtube.com/embed/G04IWiW4JNg"></iframe></div></div></div></div>
HTML));
        $testimonials = $importer->extract($html, 'https://iiprof.com/testimonials')['testimonials'];
        $this->assertSame(['Ayushi More', 'A placeholder quote.'], [$testimonials[0]['name'], $testimonials[0]['quote']]);
        $this->assertSame('https://www.youtube.com/watch?v=G04IWiW4JNg', $testimonials[1]['video']);
    }

    public function test_source_addresses_follow_iiprof_com_spelling(): void
    {
        $importer = new PageImporter;
        $this->assertSame('https://iiprof.com/management/Director-Council', $importer->sourceUrl('/management/director-council'));
        $this->assertSame('https://iiprof.com/management/Board-of-Studies', $importer->sourceUrl('/management/board-of-studies'));
        $this->assertSame('https://iiprof.com/industry/it-&-ites', $importer->sourceUrl('/industry/it-ites'));
        $this->assertSame('https://iiprof.com/about-us', $importer->sourceUrl('about-us'));
    }

    public function test_pull_site_copies_untouched_pages_and_keeps_edited_ones(): void
    {
        $this->seed(DatabaseSeeder::class);
        Page::query()->where('slug', 'privacy-policy')->update(['content' => 'Our own policy, edited here.']);
        TeamMember::query()->where('name', 'Mr. Chandan Roy')->update(['bio' => null]);

        $bio = htmlspecialchars('<p>New biography.</p>');
        Http::fake([
            'iiprof.com/about-us' => Http::response($this->page('About Us', '<h1>About Us</h1><p>Copied <b>about</b> text.</p>')),
            'iiprof.com/privacy-policy' => Http::response($this->page('Privacy Policy', '<p>Their policy.</p>')),
            'iiprof.com/management/Director-Council' => Http::response($this->page('Director Council',
                '<div class="single-team-box"><h3>Mr. Chandan Roy</h3><span class="mt-1">Consultant</span><button data-details="'.$bio.'">View</button></div>')),
            'iiprof.com/*' => Http::response('Not found', 404),
        ]);

        $paths = ['/about-us', '/privacy-policy', '/management/director-council', '/campaign', '/view-events'];
        $this->artisan('iiprof:pull-site', ['--path' => $paths])
            ->expectsOutputToContain('Done: 2 copied, 1 kept, 1 failed')
            ->assertSuccessful();

        $this->assertSame('Copied **about** text.', Page::query()->where('slug', 'about-us')->value('content'));
        $this->assertSame('Our own policy, edited here.', Page::query()->where('slug', 'privacy-policy')->value('content'));
        $this->assertSame('New biography.', TeamMember::query()->where('name', 'Mr. Chandan Roy')->value('bio'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'view-events'));

        $this->artisan('iiprof:pull-site', ['--force' => true, '--path' => ['/privacy-policy']])->assertSuccessful();
        $this->assertSame('Their policy.', Page::query()->where('slug', 'privacy-policy')->value('content'));
    }

    public function test_admin_can_fill_a_page_from_a_web_page(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::factory()->admin()->create());
        $page = Page::query()->where('slug', 'campaign')->firstOrFail();

        Http::fake([
            'iiprof.com/campaign' => Http::response($this->page('Campaign', '<p>Campaign <a href="/membership">details</a>.</p>')),
            'iiprof.com/missing' => Http::response('Nope', 404),
        ]);

        Livewire::test(Edit::class, ['page' => $page])
            ->assertSet('importUrl', 'https://iiprof.com/campaign')
            ->call('importFromUrl')
            ->assertHasNoErrors()
            ->assertSet('form.content', 'Campaign [details](https://iiprof.com/membership).')
            ->assertSet('tab', 'preview')
            ->set('importUrl', 'https://iiprof.com/missing')
            ->call('importFromUrl')
            ->assertHasErrors('importUrl')
            ->set('importUrl', 'http://127.0.0.1/admin')
            ->call('importFromUrl')
            ->assertHasErrors('importUrl');

        $this->assertNotSame('Campaign [details](https://iiprof.com/membership).', $page->fresh()->content, 'not saved until "Save page"');
    }
}
