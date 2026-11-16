<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Event;
use App\Models\Family;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SampleDataSeeder;
use Database\Seeders\SampleUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function withRole(Role $role): User
    {
        $user = User::factory()->committee()->create();
        $user->assignRole($role->value);

        return $user;
    }

    public function test_every_role_in_the_plan_exists(): void
    {
        $this->assertEqualsCanonicalizing(
            ['admin', 'treasurer', 'puja', 'catering', 'nursery', 'volunteer', 'website_editor', 'member'],
            RoleModel::query()->pluck('name')->all(),
        );
    }

    public function test_only_admin_and_website_editor_may_edit_the_website(): void
    {
        $event = Event::factory()->create();

        $this->assertTrue($this->withRole(Role::Admin)->can('update', $event));
        $this->assertTrue($this->withRole(Role::WebsiteEditor)->can('update', $event));

        foreach ([Role::Treasurer, Role::PujaInCharge, Role::CateringInCharge, Role::NurseryInCharge, Role::Volunteer] as $role) {
            $user = $this->withRole($role);
            $this->assertTrue($user->can('view', $event), $role->value.' may look');
            $this->assertFalse($user->can('update', $event), $role->value.' may not edit');
        }
    }

    public function test_role_permissions_follow_the_plan(): void
    {
        $this->assertTrue($this->withRole(Role::Treasurer)->can('fees.manage'));
        $this->assertFalse($this->withRole(Role::Treasurer)->can('catering.manage'));
        $this->assertTrue($this->withRole(Role::CateringInCharge)->can('catering.manage'));
        $this->assertTrue($this->withRole(Role::NurseryInCharge)->can('nursery.manage'));
        $this->assertTrue($this->withRole(Role::PujaInCharge)->can('chanda.record'));
        $this->assertTrue($this->withRole(Role::Volunteer)->can('passes.scan'));
        $this->assertFalse($this->withRole(Role::Volunteer)->can('fees.manage'));
        $this->assertTrue($this->withRole(Role::Admin)->can('roles.manage'));
        $this->assertFalse($this->withRole(Role::WebsiteEditor)->can('members.manage'));
    }

    public function test_members_only_see_their_own_family_and_receipts(): void
    {
        $mine = Family::factory()->create();
        $theirs = Family::factory()->create();
        $member = User::factory()->forFamily($mine)->create();
        $member->assignRole(Role::Member->value);

        $this->assertTrue($member->can('view', $mine));
        $this->assertFalse($member->can('view', $theirs));
        $this->assertTrue($member->can('view', Payment::factory()->for($mine)->create()));
        $this->assertFalse($member->can('view', Payment::factory()->for($theirs)->create()));
        $this->assertTrue($this->withRole(Role::Treasurer)->can('view', $theirs));
    }

    public function test_the_sample_users_are_created_with_roles(): void
    {
        config(['sample.admin_password' => 'admin-pass-123', 'sample.member_password' => 'member-pass-123']);
        $this->seed([BookSeeder::class, SampleDataSeeder::class, SampleUsersSeeder::class]);

        $admin = User::query()->where('email', config('sample.admin_email'))->firstOrFail();
        $member = User::query()->where('email', config('sample.member_email'))->firstOrFail();

        $this->assertTrue($admin->isCommitteeMember());
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($member->isMember());
        $this->assertSame('MC-0104', $member->family->family_no);

        $this->post('/admin/login', ['login' => $admin->email, 'password' => 'admin-pass-123'])
            ->assertRedirect(route('admin.dashboard'));
    }
}
