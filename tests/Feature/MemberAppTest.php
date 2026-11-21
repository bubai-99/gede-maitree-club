<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\Member;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\User;
use App\Support\QrCode;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MemberAppTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sample.admin_password' => 'sample-admin-pass', 'sample.member_password' => 'sample-member-pass']);
        $this->seed(DatabaseSeeder::class);
        $this->member = User::query()->where('email', config('sample.member_email'))->firstOrFail();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function screens(): array
    {
        return [
            'home' => ['/app', 'Your dues'],
            'dues' => ['/app/dues', 'Statement'],
            'receipts' => ['/app/receipts', 'MC-26-0388'],
            'card' => ['/app/card', 'Show this card at the gate'],
            'events' => ['/app/events', 'Annual Sports Day'],
            'event' => ['/app/events/sports', 'Programme'],
            'notices' => ['/app/notices', 'AGM agenda for members'],
            'profile' => ['/app/profile', 'Sourav Mukherjee'],
        ];
    }

    #[DataProvider('screens')]
    public function test_every_member_screen_loads_in_both_languages(string $url, string $text): void
    {
        $this->actingAs($this->member)->get($url)->assertOk()->assertSee($text);
        $this->actingAs($this->member)->get($url.'?lang=bn')->assertOk()->assertSee('<html lang="bn">', false);
    }

    public function test_home_shows_what_the_family_owes(): void
    {
        $this->actingAs($this->member)->get('/app')->assertSee('₹200');
    }

    public function test_a_receipt_shows_its_details(): void
    {
        $payment = Payment::query()->where('receipt_no', 'MC-26-0388')->firstOrFail();

        $this->actingAs($this->member)->get('/app/receipts/'.$payment->id)
            ->assertOk()->assertSee('MC-26-0388')->assertSee('₹300');
    }

    public function test_members_cannot_see_another_family_s_receipts(): void
    {
        $other = Payment::factory()->for(Family::factory())->create();

        $this->actingAs($this->member)->get('/app/receipts/'.$other->id)->assertForbidden();
    }

    public function test_members_only_notices_show_in_the_app_but_not_on_the_website(): void
    {
        $notice = Notice::query()->where('slug', 'agm-agenda-for-members')->firstOrFail();

        $this->actingAs($this->member)->get('/app/notices/'.$notice->slug)->assertOk();
        $this->get('/notices/'.$notice->slug)->assertNotFound();
    }

    public function test_each_card_has_a_signed_gate_qr(): void
    {
        $this->actingAs($this->member)->get('/app/card')
            ->assertSee('<svg', false)
            ->assertSee('MC-0104-1');

        $member = Member::query()->where('member_no', 'MC-0104-1')->firstOrFail();
        $code = QrCode::memberCode($member);

        $this->assertSame('MC-0104-1', QrCode::verifyMemberCode($code));
        $this->assertNull(QrCode::verifyMemberCode(str_replace('MC-0104-1', 'MC-0104-2', $code)));
        $this->assertNull(QrCode::verifyMemberCode('not a code'));
    }

    public function test_the_app_is_installable(): void
    {
        $this->actingAs($this->member)->get('/app')
            ->assertSee('rel="manifest" href="'.asset('member.webmanifest').'"', false)
            ->assertSee('data-sw="'.asset('sw.js').'"', false);

        foreach (['member.webmanifest', 'admin.webmanifest'] as $file) {
            $manifest = json_decode(File::get(public_path($file)), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame('standalone', $manifest['display']);

            foreach ($manifest['icons'] as $icon) {
                $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
            }
        }

        $this->assertStringContainsString("addEventListener('fetch'", File::get(public_path('sw.js')));
    }
}
