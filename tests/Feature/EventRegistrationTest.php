<?php

namespace Tests\Feature;

use App\Enums\FamilyStatus;
use App\Enums\NoticeAudience;
use App\Enums\PassStatus;
use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Family;
use App\Models\Notice;
use App\Models\Pass;
use App\Models\User;
use App\Passes\RegistrationException;
use App\Passes\Registrations;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M8: families register for events in the member app, get one pass per registration,
 * and the Puja in-charge sees who is coming and can cancel an event.
 */
class EventRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, BookSeeder::class]);
        $this->event = Event::factory()->create([
            'slug' => 'sports-day',
            'title' => 'Annual Sports Day',
            'starts_at' => now()->addDays(10)->setTime(7, 0),
            'pass_required' => true,
            'registration_open' => true,
            'family_limit' => 4,
            'capacity' => 6,
        ]);
    }

    private function member(?Family $family = null): User
    {
        $user = User::factory()->forFamily($family)->create();
        $user->assignRole(Role::Member->value);

        return $user;
    }

    private function committee(Role $role): User
    {
        $user = User::factory()->committee()->create();
        $user->assignRole($role->value);

        return $user;
    }

    public function test_a_member_registers_the_family_and_gets_one_pass(): void
    {
        $member = $this->member();

        $this->actingAs($member)->post('/app/events/sports-day/registration', ['people' => 3])
            ->assertRedirect('/app/events/sports-day')->assertSessionHasNoErrors();

        $registration = EventRegistration::query()->sole();
        $this->assertSame(3, $registration->people);
        $this->assertSame($member->family_id, $registration->family_id);

        $pass = Pass::query()->sole();
        $this->assertSame($registration->id, $pass->registration_id);
        $this->assertSame(3, $pass->people);
        $this->assertSame(Pass::hashToken($pass->token), $pass->token_hash);
        $this->assertNotNull(Pass::tokenFromCode($pass->qrText()));

        // Changing the head count keeps the same pass (and token).
        $this->actingAs($member)->post('/app/events/sports-day/registration', ['people' => 2])->assertSessionHasNoErrors();
        $this->assertSame(2, $pass->refresh()->people);
        $this->assertSame(1, Pass::query()->count());

        $this->actingAs($member)->get('/app/events/passes')->assertOk()->assertSee('Annual Sports Day');
        $this->actingAs($member)->get('/app/events/passes/'.$pass->id)->assertOk()->assertSee('FAMILY PASS')->assertSee('<svg', false);
        $this->actingAs($member)->get('/app/events/past')->assertOk();
        $this->actingAs($member)->get('/app/events/sports-day?lang=bn')->assertOk();
    }

    public function test_the_qr_holds_no_family_data(): void
    {
        $family = Family::factory()->create(['family_no' => 'MC-0555']);
        $registration = app(Registrations::class)->register($this->event, $family, 2, $this->member($family));
        $pass = $registration->pass;
        $this->assertNotNull($pass);

        $this->assertMatchesRegularExpression('/^GMP1\.[A-Za-z0-9_-]{32}$/', $pass->qrText());
        $this->assertStringNotContainsString('0555', $pass->qrText());
        // Only the hash is stored in clear; the token column is encrypted.
        $this->assertStringNotContainsString(substr($pass->qrText(), 5), (string) $pass->getRawOriginal('token'));
    }

    public function test_the_family_limit_is_enforced(): void
    {
        $this->actingAs($this->member())->post('/app/events/sports-day/registration', ['people' => 5])
            ->assertSessionHasErrors('people');

        $this->expectException(RegistrationException::class);
        app(Registrations::class)->register($this->event, Family::factory()->create(), 5, $this->member());
    }

    public function test_capacity_is_enforced(): void
    {
        $registrations = app(Registrations::class);
        $registrations->register($this->event, Family::factory()->create(), 4, $this->member());

        $this->actingAs($this->member())->post('/app/events/sports-day/registration', ['people' => 3])
            ->assertSessionHas('error', 'Only :count places are left.')
            ->assertSessionHas('status_replace', ['count' => 2]);

        $this->assertSame(1, EventRegistration::query()->count());
    }

    public function test_registration_closes_at_the_cut_off(): void
    {
        $this->event->update(['registration_closes_at' => now()->subHour()]);
        $member = $this->member();

        $this->actingAs($member)->post('/app/events/sports-day/registration', ['people' => 2])
            ->assertSessionHas('error', 'Registration for this event is closed.');
        $this->assertSame(0, EventRegistration::query()->count());

        // The committee may still register a family at the desk.
        $this->actingAs($this->committee(Role::PujaInCharge))->post('/admin/events/sports-day/registrations', ['family_no' => $member->family->family_no, 'people' => 2])
            ->assertSessionHas('status');
        $this->assertSame(1, EventRegistration::query()->count());
    }

    public function test_a_member_cancels_before_the_cut_off_but_not_after(): void
    {
        $member = $this->member();
        $this->actingAs($member)->post('/app/events/sports-day/registration', ['people' => 2]);

        $this->actingAs($member)->delete('/app/events/sports-day/registration')->assertSessionHas('status', 'Registration cancelled');
        $this->assertSame(RegistrationStatus::Cancelled, EventRegistration::query()->sole()->status);
        $this->assertSame(PassStatus::Cancelled, Pass::query()->sole()->status);

        // Registering again brings the pass back with a new token.
        $oldHash = Pass::query()->sole()->token_hash;
        $this->actingAs($member)->post('/app/events/sports-day/registration', ['people' => 2]);
        $this->assertTrue(Pass::query()->sole()->isActive());
        $this->assertNotSame($oldHash, Pass::query()->sole()->token_hash);

        $this->travelTo($this->event->starts_at->copy()->addMinute());
        $this->actingAs($member)->delete('/app/events/sports-day/registration')
            ->assertSessionHas('error', 'Registrations can no longer be cancelled in the app. Please call the committee.');
        $this->assertTrue(EventRegistration::query()->sole()->isActive());
    }

    public function test_members_only_events_need_an_active_family(): void
    {
        $lapsed = Family::factory()->create(['status' => FamilyStatus::Lapsed]);

        $this->actingAs($this->member($lapsed))->post('/app/events/sports-day/registration', ['people' => 1])
            ->assertSessionHas('error', 'Only families with active membership can register for this event.');
    }

    public function test_a_member_cannot_see_another_familys_pass(): void
    {
        $other = Family::factory()->create();
        $theirs = app(Registrations::class)->register($this->event, $other, 2, $this->member($other))->pass;
        $this->assertNotNull($theirs);

        $member = $this->member();
        $this->actingAs($member)->get('/app/events/passes/'.$theirs->id)->assertNotFound();
        $this->actingAs($member)->get('/app/events/passes')->assertOk()->assertDontSee($other->family_no);
    }

    public function test_draft_events_cannot_be_registered_for(): void
    {
        $this->event->unpublish();

        $this->actingAs($this->member())->post('/app/events/sports-day/registration', ['people' => 1])
            ->assertNotFound();
    }

    public function test_the_puja_in_charge_sees_registrations_and_exports_them(): void
    {
        $family = Family::factory()->create(['family_no' => 'MC-0777', 'head_name' => 'Sample Head']);
        app(Registrations::class)->register($this->event, $family, 3, $this->member($family));
        $puja = $this->committee(Role::PujaInCharge);

        $this->actingAs($puja)->get('/admin/events')->assertOk()->assertSee('Who’s coming', false);
        $this->actingAs($puja)->get('/admin/events/sports-day/registrations')->assertOk()->assertSee('MC-0777');
        $this->actingAs($puja)->get('/admin/events/sports-day/registrations?lang=bn')->assertOk();

        $csv = $this->actingAs($puja)->get('/admin/events/sports-day/registrations/export');
        $csv->assertOk();
        $this->assertStringContainsString('MC-0777', $csv->streamedContent());
    }

    public function test_cancelling_an_event_stops_passes_and_tells_members(): void
    {
        $family = Family::factory()->create(['phone' => '9830012345']);
        app(Registrations::class)->register($this->event, $family, 2, $this->member($family));
        $puja = $this->committee(Role::PujaInCharge);

        $this->actingAs($puja)->get('/admin/events/sports-day/cancel')->assertOk();
        $this->actingAs($puja)->post('/admin/events/sports-day/cancel', ['reason' => 'Ground not available'])
            ->assertRedirect('/admin/events/sports-day/registrations');

        $this->assertTrue($this->event->refresh()->isCancelled());
        $this->assertSame(PassStatus::Cancelled, Pass::query()->sole()->status);
        $notice = Notice::query()->sole();
        $this->assertSame(NoticeAudience::Members, $notice->audience);
        $this->assertTrue($notice->isPublished());

        $this->actingAs($puja)->get('/admin/events/sports-day/registrations')->assertOk()->assertSee('https://wa.me/919830012345', false);
        $this->get('/events/sports-day')->assertOk()->assertSee('This event has been cancelled.');
    }

    public function test_events_with_registrations_are_not_deleted(): void
    {
        app(Registrations::class)->register($this->event, Family::factory()->create(), 1, $this->member());

        $this->actingAs($this->committee(Role::Admin))->delete('/admin/events/sports-day')->assertSessionHas('error');
        $this->assertTrue($this->event->exists());
        $this->assertNotNull($this->event->fresh());
    }

    public function test_the_console_event_form_saves_registration_settings(): void
    {
        $this->actingAs($this->committee(Role::PujaInCharge))->put('/admin/events/sports-day', [
            'action' => 'publish',
            'title' => 'Annual Sports Day',
            'slug' => 'sports-day',
            'kind' => 'sports',
            'date' => now()->addDays(10)->toDateString(),
            'time' => '07:00',
            'pass_required' => '1',
            'registration_open' => '1',
            'members_only' => '0',
            'capacity' => '200',
            'family_limit' => '5',
            'registration_closes' => now()->addDays(8)->toDateString(),
        ])->assertRedirect('/admin/events');

        $event = $this->event->refresh();
        $this->assertSame(200, $event->capacity);
        $this->assertSame(5, $event->family_limit);
        $this->assertFalse($event->members_only);
        $this->assertTrue($event->registration_open);
        $this->assertNotNull($event->registration_closes_at);
        $this->assertTrue($event->isPublished());
    }

    public function test_switching_on_passes_later_gives_registered_families_a_pass(): void
    {
        $this->event->update(['pass_required' => false]);
        $registrations = app(Registrations::class);
        $puja = $this->committee(Role::PujaInCharge);
        $kept = $registrations->register($this->event, Family::factory()->create(), 2, $puja);
        $gone = $registrations->register($this->event, Family::factory()->create(), 2, $puja);
        $registrations->cancel($gone, $puja, true);
        $this->assertNull($kept->pass()->first());

        $this->actingAs($puja)->put('/admin/events/sports-day', [
            'action' => 'publish',
            'title' => 'Annual Sports Day',
            'slug' => 'sports-day',
            'kind' => 'sports',
            'date' => now()->addDays(10)->toDateString(),
            'time' => '07:00',
            'pass_required' => '1',
            'registration_open' => '1',
        ])->assertRedirect('/admin/events');

        $pass = $kept->pass()->first();
        $this->assertNotNull($pass);
        $this->assertTrue($pass->isActive());
        $this->assertSame(2, $pass->people);
        $this->assertNull($gone->pass()->first());
    }

    public function test_roles_that_do_not_run_events_are_kept_out(): void
    {
        foreach ([Role::WebsiteEditor, Role::NurseryInCharge, Role::Volunteer] as $role) {
            $user = $this->committee($role);
            $this->actingAs($user)->get('/admin/events/sports-day/registrations')->assertForbidden();
            $this->actingAs($user)->post('/admin/events/sports-day/cancel', ['reason' => 'Not allowed here'])->assertForbidden();
        }

        $this->assertFalse($this->event->refresh()->isCancelled());
        // Members never reach the console.
        $this->actingAs($this->member())->get('/admin/events/sports-day/registrations')->assertForbidden();
    }
}
