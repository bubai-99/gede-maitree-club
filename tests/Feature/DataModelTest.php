<?php

namespace Tests\Feature;

use App\Enums\DueStatus;
use App\Enums\EventKind;
use App\Enums\PublishStatus;
use App\Models\CateringPackage;
use App\Models\Due;
use App\Models\Event;
use App\Models\Family;
use App\Models\Member;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\Plant;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class DataModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_sample_data_seeds_and_can_be_seeded_twice(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, Event::count());
        $this->assertSame(14, Plant::count());
        $this->assertSame(3, CateringPackage::count());
        $this->assertSame(24, Family::count());
        $this->assertTrue(SiteSetting::query()->find('sample_data')?->value);

        $family = Family::query()->where('family_no', 'MC-0104')->firstOrFail();
        $this->assertCount(3, $family->members);
        $this->assertSame(200, $family->outstanding());
        $this->assertCount(3, $family->payments);
    }

    public function test_family_numbers_are_unique(): void
    {
        Family::factory()->create(['family_no' => 'MC-0001']);

        $this->expectException(UniqueConstraintViolationException::class);
        Family::factory()->create(['family_no' => 'MC-0001']);
    }

    public function test_a_family_has_members_dues_payments_and_users(): void
    {
        $family = Family::factory()->create();
        Member::factory()->head()->for($family)->create();
        Member::factory()->count(2)->for($family)->create();
        $due = Due::factory()->for($family)->create(['amount' => 100]);
        $payment = Payment::factory()->for($family)->create(['amount' => 100]);
        $payment->dues()->attach($due);
        $user = User::factory()->forFamily($family)->create();

        $this->assertCount(3, $family->members);
        $this->assertTrue($family->members->first()->is_head);
        $this->assertTrue($payment->dues->first()->is($due));
        $this->assertTrue($user->family->is($family));
        $this->assertTrue($user->isMember());
        $this->assertSame(DueStatus::Due, $due->fresh()->status);
    }

    public function test_casts_turn_columns_into_enums_dates_and_arrays(): void
    {
        $event = Event::factory()->create(['kind' => EventKind::Sports]);

        $this->assertSame(EventKind::Sports, $event->fresh()->kind);
        $this->assertIsArray($event->fresh()->schedule);
        $this->assertInstanceOf(\DateTimeInterface::class, $event->fresh()->starts_at);
        $this->assertSame(PublishStatus::Published, $event->fresh()->status);
    }

    public function test_only_published_content_is_in_the_published_scope(): void
    {
        Notice::factory()->create();
        $draft = Notice::factory()->draft()->create();

        $this->assertSame(1, Notice::query()->published()->count());

        $draft->publish();

        $this->assertSame(2, Notice::query()->published()->count());
        $this->assertNotNull($draft->fresh()->published_at);
    }

    public function test_public_notices_hide_members_only_notices(): void
    {
        Notice::factory()->create();
        Notice::factory()->membersOnly()->create();

        $this->assertSame(1, Notice::query()->forPublic()->count());
        $this->assertSame(2, Notice::query()->forMembers()->count());
    }

    public function test_bilingual_fields_fall_back_to_english(): void
    {
        $plant = Plant::factory()->create(['name' => 'Tulsi', 'name_bn' => 'তুলসী', 'about' => 'Holy basil', 'about_bn' => null]);

        App::setLocale('bn');
        $this->assertSame('তুলসী', $plant->tr('name'));
        $this->assertSame('Holy basil', $plant->tr('about'));

        App::setLocale('en');
        $this->assertSame('Tulsi', $plant->tr('name'));
    }

    public function test_serving_rates_and_team_size(): void
    {
        $package = CateringPackage::factory()->make(['rate' => 60]);

        $this->assertSame(54, $package->memberRate());
        $this->assertSame(2, CateringPackage::serversFor(10));
        $this->assertSame(5, CateringPackage::serversFor(70));
    }
}
