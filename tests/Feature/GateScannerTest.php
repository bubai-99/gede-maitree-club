<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\ScanResult;
use App\Models\Event;
use App\Models\Family;
use App\Models\Member;
use App\Models\Pass;
use App\Models\PassScan;
use App\Models\User;
use App\Passes\GateKeeper;
use App\Passes\Registrations;
use App\Support\QrCode;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * M8 gate scanner: allowed / denied / already entered, atomic admission, undo, and offline
 * scans synced later with first-scan-wins.
 */
class GateScannerTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private Family $family;

    private Pass $pass;

    private User $volunteer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, BookSeeder::class]);
        $this->event = Event::factory()->create(['slug' => 'picnic', 'starts_at' => now()->addHours(2), 'pass_required' => true, 'registration_open' => true]);
        $this->family = Family::factory()->create(['family_no' => 'MC-0104', 'head_name' => 'Ananya Mukherjee']);
        $pass = app(Registrations::class)->register($this->event, $this->family, 3, $this->user(Role::PujaInCharge))->pass;
        $this->assertNotNull($pass);
        $this->pass = $pass;
        $this->volunteer = $this->user(Role::Volunteer);
    }

    private function user(Role $role): User
    {
        $user = User::factory()->committee()->create();
        $user->assignRole($role->value);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function scan(array $data, ?User $as = null): TestResponse
    {
        return $this->actingAs($as ?? $this->volunteer)->postJson('/admin/scan/picnic/scans', ['uuid' => (string) Str::uuid(), 'gate' => 'Gate 1', ...$data]);
    }

    public function test_the_scanner_page_and_pass_list_work_for_volunteers(): void
    {
        $this->actingAs($this->volunteer)->get('/admin/scan')->assertOk()->assertSee('scan-config', false)->assertSee('Type family number instead');
        $this->actingAs($this->volunteer)->get('/admin/scan?lang=bn')->assertOk();

        $list = $this->actingAs($this->volunteer)->getJson('/admin/scan/picnic/passes')->assertOk()
            ->assertJsonPath('passes.0.h', $this->pass->token_hash)
            ->assertJsonPath('passes.0.f', 'MC-0104')
            ->assertJsonPath('passes.0.n', 3);
        // Hashes only: no token, no names.
        $this->assertStringNotContainsString($this->pass->token, $list->getContent() ?: '');
        $this->assertStringNotContainsString('Ananya', $list->getContent() ?: '');
    }

    public function test_a_valid_pass_lets_the_family_in_once(): void
    {
        $this->scan(['code' => $this->pass->qrText()])->assertOk()
            ->assertJsonPath('scan.result', 'allowed')
            ->assertJsonPath('scan.people', 3)
            ->assertJsonPath('scan.family_no', 'MC-0104')
            ->assertJsonPath('stats.inside', 3);

        $this->assertNotNull($this->pass->refresh()->used_at);
        $this->assertSame('Gate 1', $this->pass->gate);

        $other = $this->user(Role::PujaInCharge);
        $this->scan(['code' => $this->pass->qrText(), 'gate' => 'Gate 2'], $other)->assertOk()
            ->assertJsonPath('scan.result', 'already')
            ->assertJsonPath('scan.entry.gate', 'Gate 1')
            ->assertJsonPath('scan.entry.by', $this->volunteer->name);
    }

    public function test_the_same_scan_uploaded_twice_is_stored_once(): void
    {
        $uuid = (string) Str::uuid();
        $this->scan(['uuid' => $uuid, 'code' => $this->pass->qrText()])->assertJsonPath('scan.result', 'allowed');
        $this->scan(['uuid' => $uuid, 'code' => $this->pass->qrText()])->assertJsonPath('scan.result', 'allowed');

        $this->assertSame(1, PassScan::query()->count());
    }

    public function test_two_gates_scanning_at_once_admit_only_once(): void
    {
        // Both gates loaded the pass before either saved: the row lock decides.
        $stale = Pass::query()->findOrFail($this->pass->id);
        $gate = app(GateKeeper::class);

        $first = $gate->scan($this->event, $this->volunteer, ['uuid' => (string) Str::uuid(), 'code' => $stale->qrText(), 'gate' => 'Gate 1']);
        $second = $gate->scan($this->event, $this->user(Role::Volunteer), ['uuid' => (string) Str::uuid(), 'code' => $stale->qrText(), 'gate' => 'Gate 2']);

        $this->assertSame(ScanResult::Allowed, $first->result);
        $this->assertSame(ScanResult::Already, $second->result);
        $this->assertSame(1, PassScan::query()->where('result', ScanResult::Allowed->value)->count());
        $this->assertSame(3, $gate->stats($this->event)['inside']);
    }

    public function test_tampered_and_foreign_codes_are_refused(): void
    {
        $token = substr($this->pass->qrText(), 5);
        $tampered = 'GMP1.'.($token[0] === 'A' ? 'B' : 'A').substr($token, 1);

        $this->scan(['code' => $tampered])->assertJsonPath('scan.result', 'invalid');
        $this->scan(['code' => 'https://example.com/not-a-pass'])->assertJsonPath('scan.result', 'invalid');
        $this->scan(['code' => 'GMC1:MC-0104-1:000000000000'])->assertJsonPath('scan.result', 'invalid');

        $this->assertNull($this->pass->refresh()->used_at);
        $this->assertSame(3, PassScan::query()->where('result', ScanResult::Invalid->value)->count());
    }

    public function test_a_pass_for_another_event_is_refused(): void
    {
        $other = Event::factory()->create(['slug' => 'sports', 'pass_required' => true, 'registration_open' => true, 'starts_at' => now()->addDays(3)]);
        $pass = app(Registrations::class)->register($other, Family::factory()->create(), 2, $this->volunteer, byCommittee: true)->pass;
        $this->assertNotNull($pass);

        $this->scan(['code' => $pass->qrText()])->assertJsonPath('scan.result', 'wrong_event');
        $this->assertNull($pass->refresh()->used_at);
    }

    public function test_cancelled_registrations_and_families_without_a_pass_are_refused(): void
    {
        app(Registrations::class)->cancel($this->pass->registration, $this->volunteer, byCommittee: true);
        $this->scan(['code' => $this->pass->qrText()])->assertJsonPath('scan.result', 'cancelled');

        Family::factory()->create(['family_no' => 'MC-0200']);
        $this->scan(['family_no' => '200'])->assertJsonPath('scan.result', 'no_pass')->assertJsonPath('scan.family_no', 'MC-0200');
        $this->scan(['family_no' => '9999'])->assertJsonPath('scan.result', 'invalid');
    }

    public function test_a_typed_family_number_and_a_member_card_find_the_pass(): void
    {
        $this->scan(['family_no' => '0104', 'people' => 2])->assertJsonPath('scan.result', 'allowed')->assertJsonPath('scan.people', 2);

        $member = Member::factory()->create(['family_id' => $this->family->id, 'member_no' => 'MC-0104-2']);
        $this->scan(['code' => QrCode::memberCode($member)])->assertJsonPath('scan.result', 'already');
    }

    public function test_head_count_undo_and_the_two_minute_window(): void
    {
        $uuid = (string) Str::uuid();
        $this->scan(['uuid' => $uuid, 'code' => $this->pass->qrText()]);

        $this->actingAs($this->volunteer)->patchJson("/admin/scan/scans/{$uuid}", ['people' => 2])->assertOk()->assertJsonPath('scan.people', 2);
        $this->assertSame(2, $this->pass->refresh()->entered_people);

        // Someone else cannot undo it.
        $this->actingAs($this->user(Role::Volunteer))->deleteJson("/admin/scan/scans/{$uuid}")->assertStatus(422);

        $this->actingAs($this->volunteer)->deleteJson("/admin/scan/scans/{$uuid}")->assertOk()->assertJsonPath('scan.undone', true)->assertJsonPath('stats.inside', 0);
        $this->assertNull($this->pass->refresh()->used_at);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'gate', 'event' => 'undone']);

        $late = (string) Str::uuid();
        $this->scan(['uuid' => $late, 'code' => $this->pass->qrText()]);
        $this->travel(3)->minutes();
        $this->actingAs($this->volunteer)->deleteJson("/admin/scan/scans/{$late}")->assertStatus(422);
        $this->assertNotNull($this->pass->refresh()->used_at);
    }

    public function test_letting_someone_back_in(): void
    {
        $this->scan(['code' => $this->pass->qrText()]);
        $uuid = (string) Str::uuid();
        $this->scan(['uuid' => $uuid, 'code' => $this->pass->qrText()])->assertJsonPath('scan.result', 'already');

        $this->actingAs($this->volunteer)->patchJson("/admin/scan/scans/{$uuid}", ['let_in' => true])->assertOk()->assertJsonPath('scan.let_in', true);
        $this->assertSame(1, app(GateKeeper::class)->stats($this->event)['reentries']);
    }

    public function test_offline_scans_sync_and_the_first_scan_wins(): void
    {
        // Gate 2 is online and lets the family in at 10:10.
        $this->travelTo(now()->setTime(10, 10));
        $this->scan(['code' => $this->pass->qrText(), 'gate' => 'Gate 2'], $this->user(Role::PujaInCharge))->assertJsonPath('scan.result', 'allowed');

        // Gate 1 was offline: it let them in at 10:00 and undid nothing; it also checked an unknown QR.
        $this->travelTo(now()->setTime(10, 30));
        $offline = (string) Str::uuid();
        $response = $this->actingAs($this->volunteer)->postJson('/admin/scan/picnic/sync', ['ops' => [
            ['op' => 'scan', 'uuid' => $offline, 'code' => $this->pass->qrText(), 'gate' => 'Gate 1', 'scanned_at' => now()->setTime(10, 0)->toIso8601String(), 'local_result' => 'allowed', 'people' => 3],
            ['op' => 'scan', 'uuid' => (string) Str::uuid(), 'code' => 'nonsense', 'gate' => 'Gate 1', 'scanned_at' => now()->setTime(10, 5)->toIso8601String(), 'local_result' => 'invalid'],
        ]])->assertOk();

        $response->assertJsonPath('results.0.ok', true)->assertJsonPath('results.0.result', 'allowed')->assertJsonPath('results.1.result', 'invalid');

        // The earlier (offline) scan is now the entry; the online one is the duplicate.
        $pass = $this->pass->refresh();
        $this->assertSame('Gate 1', $pass->gate);
        $this->assertSame('10:00', $pass->used_at?->format('H:i'));
        $online = PassScan::query()->where('gate', 'Gate 2')->sole();
        $this->assertSame(PassScan::CONFLICT_DUPLICATE, $online->conflict);
        $this->assertNull(PassScan::query()->where('uuid', $offline)->sole()->conflict);

        // Uploading the same queue again changes nothing.
        $this->actingAs($this->volunteer)->postJson('/admin/scan/picnic/sync', ['ops' => [
            ['op' => 'scan', 'uuid' => $offline, 'code' => $this->pass->qrText(), 'gate' => 'Gate 1', 'scanned_at' => now()->setTime(10, 0)->toIso8601String(), 'local_result' => 'allowed', 'people' => 3],
        ]])->assertOk();
        $this->assertSame(3, PassScan::query()->count());
    }

    public function test_a_later_offline_scan_is_reported_as_a_duplicate(): void
    {
        $this->scan(['code' => $this->pass->qrText()]);
        $this->travel(5)->minutes();

        $uuid = (string) Str::uuid();
        $this->actingAs($this->user(Role::Volunteer))->postJson('/admin/scan/picnic/sync', ['ops' => [
            ['op' => 'scan', 'uuid' => $uuid, 'code' => $this->pass->qrText(), 'scanned_at' => now()->subMinute()->toIso8601String(), 'local_result' => 'allowed', 'people' => 2],
        ]])->assertOk()->assertJsonPath('duplicates.0.uuid', $uuid)->assertJsonPath('duplicates.0.conflict', 'duplicate');
    }

    public function test_offline_undo_and_head_count_are_applied_in_order(): void
    {
        $uuid = (string) Str::uuid();
        $at = now();
        $this->actingAs($this->volunteer)->postJson('/admin/scan/picnic/sync', ['ops' => [
            ['op' => 'scan', 'uuid' => $uuid, 'family_no' => 'MC-0104', 'scanned_at' => $at->toIso8601String(), 'local_result' => 'allowed', 'people' => 3],
            ['op' => 'people', 'uuid' => $uuid, 'people' => 1, 'at' => $at->copy()->addSeconds(20)->toIso8601String()],
            ['op' => 'undo', 'uuid' => $uuid, 'at' => $at->copy()->addSeconds(40)->toIso8601String()],
        ]])->assertOk()->assertJsonPath('results.2.ok', true);

        $this->assertNotNull(PassScan::query()->where('uuid', $uuid)->sole()->undone_at);
        $this->assertNull($this->pass->refresh()->used_at);
    }

    public function test_only_scanning_roles_may_use_the_scanner(): void
    {
        foreach ([Role::WebsiteEditor, Role::NurseryInCharge, Role::Treasurer] as $role) {
            $user = $this->user($role);
            $this->actingAs($user)->get('/admin/scan')->assertForbidden();
            $this->actingAs($user)->postJson('/admin/scan/picnic/scans', ['uuid' => (string) Str::uuid(), 'code' => $this->pass->qrText()])->assertForbidden();
        }

        $member = User::factory()->forFamily($this->family)->create();
        $member->assignRole(Role::Member->value);
        $this->actingAs($member)->getJson('/admin/scan/picnic/passes')->assertForbidden();

        $this->assertNull($this->pass->refresh()->used_at);
    }

    public function test_the_scan_endpoints_need_the_csrf_token(): void
    {
        // Laravel skips the CSRF check while running tests, so check that the route is in the web group.
        $route = app('router')->getRoutes()->getByName('admin.scan.sync');
        $this->assertNotNull($route);
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertContains('throttle:scanner', $route->gatherMiddleware());
    }
}
