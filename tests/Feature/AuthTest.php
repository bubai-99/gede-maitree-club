<?php

namespace Tests\Feature;

use App\Contracts\OtpSender;
use App\Enums\Role;
use App\Models\Family;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function member(array $attributes = []): User
    {
        $user = User::factory()->forFamily()->create(['phone' => '9831734917', 'password' => 'secret-pass-1', ...$attributes]);
        $user->assignRole(Role::Member->value);

        return $user;
    }

    private function committee(Role $role = Role::Treasurer, array $attributes = []): User
    {
        $user = User::factory()->committee()->create(['password' => 'secret-pass-1', ...$attributes]);
        $user->assignRole($role->value);

        return $user;
    }

    public function test_sign_in_pages_load_in_both_languages(): void
    {
        $this->get('/app/login')->assertOk()->assertSee('Member sign-in');
        $this->get('/admin/login')->assertOk()->assertSee('Committee sign-in');
        $this->get('/app/login?lang=bn')->assertOk()->assertSee('সদস্য লগইন');
    }

    public function test_a_member_signs_in_with_mobile_number_and_password(): void
    {
        $user = $this->member();

        $this->post('/app/login', ['login' => '+91 98317 34917', 'password' => 'secret-pass-1'])
            ->assertRedirect(route('member.home'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->get('/app')->assertOk()->assertSee($user->name);
    }

    public function test_a_member_signs_in_with_email(): void
    {
        $user = $this->member(['email' => 'ananya@example.org']);

        $this->post('/app/login', ['login' => 'Ananya@Example.org', 'password' => 'secret-pass-1'])
            ->assertRedirect(route('member.home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_is_refused(): void
    {
        $this->member();

        $this->from('/app/login')->post('/app/login', ['login' => '9831734917', 'password' => 'wrong'])
            ->assertRedirect('/app/login')
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_repeated_failures_lock_the_login(): void
    {
        $this->member();

        foreach (range(1, 5) as $try) {
            $this->post('/app/login', ['login' => '9831734917', 'password' => 'wrong']);
        }

        $this->post('/app/login', ['login' => '9831734917', 'password' => 'secret-pass-1'])
            ->assertSessionHasErrors('login');

        $this->assertStringContainsString('Too many login attempts', (string) session('errors')->first('login'));
        $this->assertGuest();
    }

    public function test_a_committee_only_account_cannot_use_the_member_sign_in(): void
    {
        $this->committee(attributes: ['phone' => '9830000001']);

        $this->post('/app/login', ['login' => '9830000001', 'password' => 'secret-pass-1'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_a_member_cannot_use_the_committee_sign_in(): void
    {
        $this->member();

        $this->post('/admin/login', ['login' => '9831734917', 'password' => 'secret-pass-1'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_a_committee_member_signs_in_to_the_console(): void
    {
        $user = $this->committee(attributes: ['email' => 'treasurer@example.org']);

        $this->post('/admin/login', ['login' => 'treasurer@example.org', 'password' => 'secret-pass-1'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get('/admin')->assertOk();
    }

    public function test_guests_are_sent_to_the_right_sign_in_page(): void
    {
        $this->get('/app')->assertRedirect(route('member.login'));
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_members_cannot_open_the_console_but_may_switch_to_a_committee_account(): void
    {
        $member = $this->member(['name' => 'Sample Member']);

        // Both apps share one sign-in: a member is offered the committee sign-in, not a bare 403.
        $this->actingAs($member)->get('/admin')->assertRedirect(route('admin.login'));
        $this->actingAs($member)->get('/admin/khata')->assertRedirect(route('admin.login'));
        $this->actingAs($member)->get('/admin/login')->assertOk()->assertSee('You are signed in as a member: Sample Member', false);

        $committee = $this->committee(Role::Treasurer, ['email' => 'treasurer@example.org', 'password' => 'secret-pass-2']);
        $this->actingAs($member)->post('/admin/login', ['login' => 'treasurer@example.org', 'password' => 'secret-pass-2'])->assertRedirect(route('admin.dashboard'));
        $this->assertSame($committee->id, auth()->id());

        // Already on the committee: the sign-in page goes straight to the console.
        $this->actingAs($committee)->get('/admin/login')->assertRedirect(route('admin.dashboard'));
    }

    public function test_committee_only_accounts_are_sent_from_the_member_app_to_the_console(): void
    {
        $this->actingAs($this->committee())->get('/app')->assertRedirect(route('admin.dashboard'));
    }

    public function test_someone_on_the_committee_and_in_a_family_can_use_both(): void
    {
        $user = $this->committee(attributes: ['family_id' => Family::factory()->create()->id]);

        $this->actingAs($user)->get('/app')->assertOk();
        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_signing_out_ends_the_session(): void
    {
        $this->actingAs($this->member())->post('/app/logout')->assertRedirect(route('member.login'));

        $this->assertGuest();
    }

    public function test_signed_in_people_skip_the_sign_in_page(): void
    {
        $this->actingAs($this->member())->get('/app/login')->assertRedirect(route('member.home'));
    }

    public function test_password_reset_link_is_sent_without_revealing_accounts(): void
    {
        Notification::fake();
        $user = $this->member(['email' => 'ananya@example.org']);

        $this->post('/forgot-password', ['email' => 'ananya@example.org'])->assertSessionHas('status');
        $this->post('/forgot-password', ['email' => 'nobody@example.org'])->assertSessionHas('status')->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_a_password_can_be_reset_with_the_token(): void
    {
        $user = $this->member(['email' => 'ananya@example.org']);
        $token = Password::createToken($user);

        $this->get('/reset-password/'.$token.'?email=ananya@example.org')->assertOk();

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'ananya@example.org',
            'password' => 'a-new-password',
            'password_confirmation' => 'a-new-password',
        ])->assertRedirect(route('member.login'));

        $this->post('/app/login', ['login' => 'ananya@example.org', 'password' => 'a-new-password'])
            ->assertRedirect(route('member.home'));
    }

    public function test_a_member_signs_in_with_a_one_time_code(): void
    {
        $sender = new class implements OtpSender
        {
            public ?string $code = null;

            public function send(string $phone, string $code): void
            {
                $this->code = $code;
            }
        };
        $this->app->instance(OtpSender::class, $sender);
        $user = $this->member();

        $this->post('/app/login/code', ['phone' => '98317 34917'])->assertRedirect(route('member.otp.show'));
        $this->assertNotNull($sender->code);

        $this->post('/app/login/code/verify', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/app/login/code/verify', ['code' => $sender->code])->assertRedirect(route('member.home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_no_code_is_sent_to_an_unknown_number_but_the_reply_is_the_same(): void
    {
        $sender = new class implements OtpSender
        {
            public int $sent = 0;

            public function send(string $phone, string $code): void
            {
                $this->sent++;
            }
        };
        $this->app->instance(OtpSender::class, $sender);

        $this->post('/app/login/code', ['phone' => '9000000000'])
            ->assertRedirect(route('member.otp.show'))
            ->assertSessionHas('status');

        $this->assertSame(0, $sender->sent);
    }
}
