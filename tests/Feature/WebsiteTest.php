<?php

namespace Tests\Feature;

use App\Models\CateringPackage;
use App\Models\Event;
use App\Models\Notice;
use App\Models\Plant;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sample.admin_password' => 'sample-admin-pass', 'sample.member_password' => 'sample-member-pass']);
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function pages(): array
    {
        return [
            'home' => ['/', 'Twelve years of pujas, play and looking out for each other'],
            'events' => ['/events', 'Blood Donation Camp'],
            'event' => ['/events/sports', 'Annual Sports Day'],
            'notices' => ['/notices', 'Notice board'],
            'catering' => ['/catering', 'Packages, per guest'],
            'nursery' => ['/nursery', 'Hibiscus (Joba)'],
            'gallery' => ['/gallery', 'Durga Puja'],
            'chanda' => ['/chanda', 'Lane by lane'],
            'about' => ['/about', 'Committee 2025–27'],
            'contact' => ['/contact', 'Write to us'],
            'join' => ['/join', 'Send application'],
            'volunteer' => ['/volunteer', 'Sign up to volunteer'],
            'panjika' => ['/panjika', 'Pujas and parbon'],
            'privacy' => ['/privacy', 'What we keep'],
            'terms' => ['/terms', 'Member cards are not transferable.'],
        ];
    }

    #[DataProvider('pages')]
    public function test_every_page_loads_in_english_and_bengali(string $url, string $text): void
    {
        $this->get($url)->assertOk()->assertSee($text, false)->assertSee('<link rel="canonical"', false);
        $this->get($url.(str_contains($url, '?') ? '&' : '?').'lang=bn')->assertOk()->assertSee('<html lang="bn">', false);
    }

    public function test_pages_use_seo_text_from_the_settings(): void
    {
        $this->get('/catering')->assertSee('<title>', false)->assertSee('property="og:description"', false)->assertSee('"@type":"NGO"', false);
        $this->get('/events/sports')->assertSee('"@type":"Event"', false);
    }

    public function test_drafts_are_never_shown(): void
    {
        $draft = Event::factory()->draft()->create(['title' => 'Secret draft event', 'slug' => 'secret-draft']);
        Notice::factory()->draft()->create(['title' => 'Secret draft notice', 'slug' => 'secret-notice']);
        Plant::factory()->draft()->create(['name' => 'Secret draft plant']);
        CateringPackage::factory()->draft()->create(['name' => 'Secret draft package']);

        $this->get('/events')->assertDontSee('Secret draft event');
        $this->get('/events/'.$draft->slug)->assertNotFound();
        $this->get('/notices')->assertDontSee('Secret draft notice');
        $this->get('/notices/secret-notice')->assertNotFound();
        $this->get('/nursery')->assertDontSee('Secret draft plant');
        $this->get('/catering')->assertDontSee('Secret draft package');
        $this->get('/sitemap.xml')->assertDontSee('secret-draft');
    }

    public function test_members_only_notices_are_not_on_the_website(): void
    {
        $notice = Notice::factory()->membersOnly()->create(['title' => 'Members only notice']);

        $this->get('/notices')->assertDontSee('Members only notice');
        $this->get('/notices/'.$notice->slug)->assertNotFound();
    }

    public function test_events_can_be_filtered_by_kind(): void
    {
        $this->get('/events?kind=sports')->assertOk()->assertSee('Annual Sports Day')->assertDontSee('Blood Donation Camp</span>', false);
        $this->get('/events?kind=nonsense')->assertRedirect();
    }

    public function test_the_custom_404_page(): void
    {
        $this->get('/no-such-page')->assertNotFound()->assertSee('We couldn’t find that page', false);
    }

    public function test_sitemap_and_robots(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('events.show', 'sports'), false)
            ->assertSee(route('catering'), false);

        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_photographers_are_credited_without_the_unsplash_name(): void
    {
        $this->get('/')->assertSee('unsplash.com/photos', false)->assertDontSee('/ Unsplash', false);
        $this->get('/nursery')->assertSee('Robert Thiemann', false)->assertDontSee('Unsplash</', false);
    }

    public function test_the_footer_has_no_sample_notes(): void
    {
        $this->get('/')->assertDontSee('Sample content: names, numbers and photos are placeholders.')->assertDontSee('Sample photos');
    }
}
