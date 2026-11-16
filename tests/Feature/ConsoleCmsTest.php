<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Enums\Role;
use App\Models\CateringPackage;
use App\Models\CommitteeMember;
use App\Models\Enquiry;
use App\Models\Event;
use App\Models\Notice;
use App\Models\Page;
use App\Models\Plant;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ConsoleCmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function as(Role $role): User
    {
        $user = User::factory()->committee()->create();
        $user->assignRole($role->value);
        $this->actingAs($user);

        return $user;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function consolePages(): array
    {
        return [
            'dashboard' => ['/admin'],
            'website' => ['/admin/website'],
            'events' => ['/admin/events'],
            'new event' => ['/admin/events/create'],
            'notices' => ['/admin/notices'],
            'plants' => ['/admin/plants'],
            'packages' => ['/admin/packages'],
            'committee' => ['/admin/committee'],
            'pages' => ['/admin/pages'],
            'page' => ['/admin/pages/catering'],
            'settings' => ['/admin/settings'],
            'enquiries' => ['/admin/enquiries'],
            'audit log' => ['/admin/activity'],
        ];
    }

    #[DataProvider('consolePages')]
    public function test_the_admin_can_open_every_console_page(string $url): void
    {
        Event::factory()->create();
        Enquiry::factory()->create();
        $this->as(Role::Admin);

        $this->get($url)->assertOk();
        $this->get($url.'?lang=bn')->assertOk();
    }

    public function test_the_dashboard_shows_real_counts(): void
    {
        Enquiry::factory()->count(3)->create();
        $this->as(Role::Treasurer);

        $this->get('/admin')->assertOk()->assertSee('Website enquiries')->assertSeeInOrder(['Website enquiries', '3']);
    }

    public function test_a_website_editor_adds_an_event_as_a_draft_then_publishes_it(): void
    {
        Storage::fake('public');
        $editor = $this->as(Role::WebsiteEditor);

        $this->post('/admin/events', [
            'action' => 'draft',
            'title' => 'Bijoya Sammilani',
            'title_bn' => 'বিজয়া সম্মিলনী',
            'slug' => 'bijoya',
            'kind' => 'social',
            'date' => now()->addMonth()->toDateString(),
            'time' => '18:00',
            'place' => 'Club room',
            'pass_required' => '1',
            'schedule' => "6:00 pm | ৬:০০ | Songs | গান\n7:30 pm | ৭:৩০ | Dinner | খাওয়া",
            'bring' => 'Your pass | সদস্য পাস',
            'photo_upload' => UploadedFile::fake()->image('bijoya.jpg', 1200, 800),
        ])->assertRedirect(route('admin.events.index'));

        $event = Event::query()->where('slug', 'bijoya')->firstOrFail();
        $this->assertSame(PublishStatus::Draft, $event->status);
        $this->assertCount(2, $event->schedule);
        $this->assertSame('Dinner', $event->schedule[1]['what']);
        $this->assertTrue($event->pass_required);
        $this->assertTrue($event->hasUploadedPhoto());

        // Drafts stay off the public site.
        $this->get('/events/bijoya')->assertNotFound();

        $this->post('/admin/events/bijoya/publish')->assertRedirect();
        $this->assertTrue($event->fresh()->isPublished());
        $this->get('/events/bijoya')->assertOk()->assertSee('Bijoya Sammilani');

        // Every change is in the audit log, with who made it.
        $this->assertTrue(Activity::query()->where('subject_type', Event::class)->where('causer_id', $editor->id)->exists());
    }

    public function test_editing_and_unpublishing_an_event(): void
    {
        $this->as(Role::Admin);
        $event = Event::factory()->create(['slug' => 'camp']);

        $this->put('/admin/events/camp', [
            'action' => 'publish', 'title' => 'Blood camp 2027', 'slug' => 'camp', 'kind' => 'service', 'date' => now()->addWeek()->toDateString(),
        ])->assertRedirect(route('admin.events.index'));
        $this->assertSame('Blood camp 2027', $event->fresh()->title);

        $this->delete('/admin/events/camp/publish')->assertRedirect();
        $this->assertFalse($event->fresh()->isPublished());
    }

    public function test_uploads_must_be_images(): void
    {
        Storage::fake('public');
        $this->as(Role::Admin);

        $this->post('/admin/plants', [
            'action' => 'draft', 'name' => 'Tulsi', 'slug' => 'tulsi', 'category' => 'medicinal', 'price' => 50, 'member_price' => 45, 'stock' => 10,
            'photo_upload' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
        ])->assertSessionHasErrors('photo_upload');

        $this->assertSame(0, Plant::count());
    }

    public function test_slugs_must_be_unique(): void
    {
        $this->as(Role::Admin);
        Notice::factory()->create(['slug' => 'taken']);

        $this->post('/admin/notices', ['action' => 'draft', 'title' => 'X', 'slug' => 'taken', 'summary' => 'x', 'audience' => 'public'])
            ->assertSessionHasErrors('slug');
    }

    public function test_notices_plants_packages_and_committee_can_be_saved(): void
    {
        $this->as(Role::WebsiteEditor);

        $this->post('/admin/notices', ['action' => 'publish', 'title' => 'Water cut', 'slug' => 'water-cut', 'summary' => 'No water on Sunday.', 'audience' => 'public', 'pinned' => '1'])
            ->assertSessionHasNoErrors();
        $this->post('/admin/plants', ['action' => 'publish', 'name' => 'Tulsi', 'slug' => 'tulsi', 'category' => 'medicinal', 'price' => 50, 'member_price' => 45, 'stock' => 10, 'active' => '1'])
            ->assertSessionHasNoErrors();
        $this->post('/admin/packages', ['action' => 'publish', 'name' => 'Tea service', 'slug' => 'tea', 'rate' => 20, 'min_guests' => 30, 'menu' => "Tea\nBiscuits"])
            ->assertSessionHasNoErrors();
        $this->post('/admin/committee', ['action' => 'publish', 'name' => 'Sample Person', 'role' => 'Joint secretary', 'since' => 2025])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Notice::query()->where('slug', 'water-cut')->sole()->pinned);
        $this->assertSame(['Tea', 'Biscuits'], CateringPackage::query()->where('slug', 'tea')->sole()->menu);
        $this->get('/notices')->assertSee('Water cut');
        $this->get('/nursery')->assertSee('Tulsi');
        $this->get('/catering')->assertSee('Tea service');
        $this->get('/about')->assertSee('Joint secretary');
        $this->assertSame(1, CommitteeMember::count());
    }

    public function test_other_committee_roles_can_look_but_not_edit(): void
    {
        $event = Event::factory()->create(['slug' => 'look']);
        $this->as(Role::Treasurer);

        $this->get('/admin/events')->assertOk()->assertDontSee(route('admin.events.create'));
        $this->get('/admin/events/look/edit')->assertOk()->assertSee('You can look but not change this.');
        $this->get('/admin/events/create')->assertForbidden();
        $this->post('/admin/events', ['action' => 'draft', 'title' => 'X', 'slug' => 'x', 'kind' => 'puja', 'date' => '2030-01-01'])->assertForbidden();
        $this->put('/admin/events/look', ['action' => 'draft', 'title' => 'X', 'slug' => 'look', 'kind' => 'puja', 'date' => '2030-01-01'])->assertForbidden();
        $this->post('/admin/events/look/publish')->assertForbidden();
        $this->delete('/admin/events/look')->assertForbidden();
        $this->put('/admin/pages/catering', ['action' => 'publish'])->assertForbidden();
        $this->put('/admin/settings', ['contact' => ['address' => 'x', 'phone' => '1', 'whatsapp' => '919830012345']])->assertForbidden();
        $this->get('/admin/activity')->assertForbidden();

        $this->assertSame($event->title, $event->fresh()->title);
    }

    public function test_page_text_is_drafted_then_published(): void
    {
        $this->as(Role::WebsiteEditor);

        $this->put('/admin/pages/catering', ['action' => 'draft', 'fields' => ['title' => 'Our serving team', 'title_bn' => 'আমাদের পরিবেশন দল']])
            ->assertRedirect(route('admin.pages.edit', 'catering'));
        $this->get('/catering')->assertDontSee('Our serving team');

        $this->put('/admin/pages/catering', ['action' => 'publish', 'fields' => ['title' => 'Our serving team', 'title_bn' => 'আমাদের পরিবেশন দল']]);
        $this->get('/catering')->assertSee('Our serving team');
        $this->get('/catering?lang=bn')->assertSee('আমাদের পরিবেশন দল');
        $this->assertFalse(Page::query()->where('key', 'catering')->sole()->hasUnpublishedChanges());

        $this->get('/admin/pages/nonsense')->assertNotFound();
    }

    public function test_settings_change_the_site_and_are_logged(): void
    {
        $admin = $this->as(Role::Admin);

        $this->put('/admin/settings', [
            'contact' => ['address' => '1 New Road, Kolkata', 'phone' => '+91 90000 00000', 'whatsapp' => '919000000000', 'upi' => 'club@bank'],
            'menu' => [['route' => 'gallery', 'show' => '0'], ['route' => 'events.index', 'show' => '1', 'label' => 'What is on', 'sort' => 0]],
            'seo' => ['catering' => ['title' => 'Serving team for hire']],
        ])->assertRedirect(route('admin.settings.edit'))->assertSessionHasNoErrors();

        $this->get('/contact')->assertSee('1 New Road, Kolkata');
        $this->get('/')->assertSee('<span>What is on</span>', false)->assertDontSee('<span>Gallery</span>', false);
        $this->get('/catering')->assertSee('<title>Serving team for hire</title>', false);
        $this->assertSame('919000000000', SiteSetting::query()->find('contact')?->value['whatsapp']);
        $this->assertTrue(Activity::query()->where('causer_id', $admin->id)->where('description', 'like', '%contact%')->exists());
    }

    public function test_enquiries_can_be_marked_as_handled(): void
    {
        $enquiry = Enquiry::factory()->create(['name' => 'Sen family']);
        $editor = $this->as(Role::WebsiteEditor);

        $this->get('/admin/enquiries')->assertSee('Sen family');
        $this->get('/admin/enquiries/'.$enquiry->id)->assertOk();
        $this->put('/admin/enquiries/'.$enquiry->id, ['handled' => '1'])->assertRedirect();

        $this->assertNotNull($enquiry->fresh()->handled_at);
        $this->assertSame($editor->id, $enquiry->fresh()->handled_by);
        $this->get('/admin/enquiries')->assertDontSee('Sen family');
    }

    public function test_members_cannot_reach_the_cms(): void
    {
        $member = User::factory()->forFamily()->create();
        $member->assignRole(Role::Member->value);

        $this->actingAs($member)->get('/admin/events')->assertForbidden();
        $this->actingAs($member)->post('/admin/notices', [])->assertForbidden();
    }
}
