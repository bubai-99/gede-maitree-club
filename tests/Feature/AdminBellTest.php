<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Enquiry;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The console's bell: what is waiting for the person signed in (by role) and the latest notices.
 */
class AdminBellTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, BookSeeder::class]);
        Payment::factory()->create(['status' => PaymentStatus::AwaitingConfirmation]);
        Enquiry::factory()->create();
        Notice::factory()->create(['title' => 'Committee meets on Friday', 'published_at' => now()->subDay()]);
    }

    private function bellFor(Role $role): string
    {
        $user = User::factory()->committee()->create();
        $user->assignRole($role->value);
        $html = $this->actingAs($user)->get('/admin')->assertOk()->getContent();

        return (string) strstr((string) strstr($html, 'id="alert-panel"'), 'All notices', true);
    }

    public function test_the_treasurer_sees_payments_to_confirm_and_the_notices(): void
    {
        $bell = $this->bellFor(Role::Admin);

        $this->assertStringContainsString('1 UPI payment to confirm', $bell);
        $this->assertStringContainsString('1 website enquiry to answer', $bell);
        $this->assertStringContainsString('Committee meets on Friday', $bell);
    }

    public function test_each_role_sees_only_what_it_can_act_on(): void
    {
        $bell = $this->bellFor(Role::WebsiteEditor);

        $this->assertStringNotContainsString('UPI payment to confirm', $bell);
        $this->assertStringContainsString('1 website enquiry to answer', $bell);
        $this->assertStringContainsString('Committee meets on Friday', $bell);
    }
}
