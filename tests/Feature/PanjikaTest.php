<?php

namespace Tests\Feature;

use App\Enums\PanjikaKind;
use App\Enums\Role;
use App\Models\PanjikaDay;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanjikaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, BookSeeder::class]);
    }

    private function user(Role $role): User
    {
        $user = User::factory()->committee()->create();
        $user->assignRole($role->value);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function pageData(string $html): array
    {
        $this->assertSame(1, preg_match('#<script type="application/json" id="panjika-data">(.*?)</script>#s', $html, $m));

        return json_decode($m[1], true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_the_puja_in_charge_adds_a_puja_and_a_wedding_day(): void
    {
        $puja = $this->user(Role::PujaInCharge);
        $date = now()->addDays(10)->toDateString();

        $this->actingAs($puja)->post('/admin/panjika', ['kind' => 'puja', 'date' => $date, 'name' => 'Jagaddhatri Puja', 'name_bn' => 'জগদ্ধাত্রী পূজা', 'tithi' => 8])->assertSessionHas('status');
        $this->actingAs($puja)->post('/admin/panjika', ['kind' => 'wedding', 'date' => $date, 'lagna' => '8:10 pm – 1:40 am'])->assertSessionHas('status');
        $this->actingAs($puja)->post('/admin/panjika', ['kind' => 'wedding', 'date' => $date])->assertSessionHasErrors('lagna');
        $this->actingAs($puja)->post('/admin/panjika', ['kind' => 'puja', 'date' => now()->subDay()->toDateString(), 'name' => 'Old'])->assertSessionHasErrors('date');

        $this->assertSame(1, PanjikaDay::query()->where('kind', PanjikaKind::Wedding->value)->count());
        $this->assertSame(8, PanjikaDay::query()->where('name', 'Jagaddhatri Puja')->value('tithi'));

        $this->actingAs($puja)->get('/admin/panjika')->assertOk()->assertSee('Jagaddhatri Puja')->assertSee('data-panjika-console', false);
    }

    public function test_the_website_page_and_the_home_box_get_the_days(): void
    {
        PanjikaDay::query()->create(['date' => now()->addDays(3)->toDateString(), 'kind' => PanjikaKind::Puja, 'name' => 'Rash Purnima', 'name_bn' => 'রাসপূর্ণিমা', 'tithi' => 14]);
        $this->actingAs($this->user(Role::WebsiteEditor))->put('/admin/panjika/source', ['source' => 'Gupta Press panjika', 'source_bn' => 'গুপ্তপ্রেস পঞ্জিকা'])->assertSessionHas('status');

        $page = $this->get('/panjika')->assertOk()->assertSee('data-panjika-page', false);
        $data = $this->pageData($page->getContent());
        $this->assertSame('Rash Purnima', $data['days'][0]['en']);
        $this->assertSame(14, $data['days'][0]['tithi']);
        $this->assertSame('Gupta Press panjika', $data['source']['en']);

        $this->get('/')->assertOk()->assertSee('data-panjika-today', false);
    }

    public function test_only_the_puja_in_charge_website_editor_and_admin_change_it(): void
    {
        $day = PanjikaDay::query()->create(['date' => now()->addDays(3)->toDateString(), 'kind' => PanjikaKind::Parbon, 'name' => 'Poush Sankranti']);

        foreach ([Role::Treasurer, Role::Volunteer, Role::CateringInCharge] as $role) {
            $user = $this->user($role);
            $this->actingAs($user)->get('/admin/panjika')->assertOk()->assertDontSee('Add puja or parbon');
            $this->actingAs($user)->post('/admin/panjika', ['kind' => 'parbon', 'date' => now()->addDay()->toDateString(), 'name' => 'X'])->assertForbidden();
            $this->actingAs($user)->delete("/admin/panjika/{$day->id}")->assertForbidden();
        }

        $this->actingAs($this->user(Role::Admin))->delete("/admin/panjika/{$day->id}")->assertSessionHas('status');
        $this->assertSame(0, PanjikaDay::query()->count());
    }

    public function test_filling_the_festivals_saves_them_marked_calculated(): void
    {
        PanjikaDay::query()->create(['date' => now()->addDays(40)->toDateString(), 'kind' => PanjikaKind::Puja, 'name' => 'Saraswati Puja']);
        $puja = $this->user(Role::PujaInCharge);

        $this->actingAs($puja)->get('/admin/panjika')->assertOk()->assertSee('Fill a year’s festivals')->assertSee('Work out the dates');

        $rows = [
            ['date' => now()->addDays(40)->toDateString(), 'kind' => 'puja', 'name' => 'Saraswati Puja', 'name_bn' => 'সরস্বতী পূজা', 'tithi' => 4],
            ['date' => now()->addDays(60)->toDateString(), 'kind' => 'puja', 'name' => 'Shivaratri', 'name_bn' => 'শিবরাত্রি', 'tithi' => 28],
            ['date' => now()->addDays(80)->toDateString(), 'kind' => 'parbon', 'name' => 'Choitro Sankranti · Charak', 'name_bn' => '', 'tithi' => ''],
        ];
        $this->actingAs($puja)->post('/admin/panjika/fill', ['rows' => $rows])
            ->assertRedirect(route('admin.panjika.index'))->assertSessionHas('status');

        // The one already in the list is skipped; the new ones are marked calculated
        $this->assertSame(1, PanjikaDay::query()->where('name', 'Saraswati Puja')->count());
        $shivaratri = PanjikaDay::query()->where('name', 'Shivaratri')->firstOrFail();
        $this->assertTrue($shivaratri->calculated);
        $this->assertSame(28, $shivaratri->tithi);
        $this->assertNull(PanjikaDay::query()->where('name', 'Choitro Sankranti · Charak')->value('tithi'));

        $this->actingAs($puja)->get('/admin/panjika')->assertSee('Calculated')->assertSee('Mark checked');
        $this->actingAs($puja)->put("/admin/panjika/{$shivaratri->id}/checked")->assertSessionHas('status');
        $this->assertFalse($shivaratri->fresh()->calculated);
    }

    public function test_filling_refuses_past_dates_weddings_and_people_who_cannot_edit(): void
    {
        $row = ['date' => now()->addDays(10)->toDateString(), 'kind' => 'puja', 'name' => 'Kali Puja'];

        $this->actingAs($this->user(Role::WebsiteEditor))->post('/admin/panjika/fill', ['rows' => [[...$row, 'date' => now()->subDay()->toDateString()]]])
            ->assertSessionHasErrors('rows.0.date');
        $this->actingAs($this->user(Role::WebsiteEditor))->post('/admin/panjika/fill', ['rows' => [[...$row, 'kind' => 'wedding']]])
            ->assertSessionHasErrors('rows.0.kind');
        $this->actingAs($this->user(Role::Treasurer))->post('/admin/panjika/fill', ['rows' => [$row]])->assertForbidden();

        $this->assertSame(0, PanjikaDay::query()->count());
    }
}
