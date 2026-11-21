<?php

namespace Tests\Feature;

use App\Enums\EnquiryKind;
use App\Enums\Role;
use App\Models\Enquiry;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreasTest extends TestCase
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

    public function test_the_sample_lanes_show_until_the_committee_saves_its_own(): void
    {
        $this->get('/volunteer')->assertOk()->assertSee('Lane 1')->assertSee('Somewhere else');
    }

    public function test_the_committee_edits_the_lanes_and_the_forms_follow(): void
    {
        $editor = $this->user(Role::WebsiteEditor);
        $this->actingAs($editor)->get('/admin/settings/areas')->assertOk()->assertSee('Lanes and areas');

        $this->actingAs($editor)->put('/admin/settings/areas', ['areas' => [
            ['name' => 'Station Road', 'name_bn' => 'স্টেশন রোড'],
            ['name' => 'Kalibari Lane', 'name_bn' => ''],
        ]])->assertRedirect('/admin/settings/areas')->assertSessionHas('status');

        $this->get('/join')->assertSee('Station Road')->assertSee('Kalibari Lane')->assertSee('Somewhere else')->assertDontSee('Pukur Par');
        $this->withSession(['locale' => 'bn'])->get('/join')->assertSee('স্টেশন রোড');

        // The forms accept the new lanes and refuse the old ones
        $form = ['name' => 'Sample Person', 'phone' => '98300 11111', 'skills' => ['gate'], 'times' => ['pujas']];
        $this->post('/volunteer', [...$form, 'area' => 'Station Road'])->assertSessionHasNoErrors();
        $this->post('/volunteer', [...$form, 'area' => 'Pukur Par'])->assertSessionHasErrors('area');
        $this->assertSame('Station Road', Enquiry::query()->where('kind', EnquiryKind::Volunteer->value)->sole()->payload['area']);
    }

    public function test_names_must_be_filled_and_different_and_only_editors_save(): void
    {
        $this->actingAs($this->user(Role::WebsiteEditor))->put('/admin/settings/areas', ['areas' => [['name' => 'Lane 1'], ['name' => 'lane 1'], ['name' => '']]])
            ->assertSessionHasErrors(['areas.1.name', 'areas.2.name']);

        $treasurer = $this->user(Role::Treasurer);
        $this->actingAs($treasurer)->get('/admin/settings/areas')->assertOk()->assertDontSee('Add a lane or area');
        $this->actingAs($treasurer)->put('/admin/settings/areas', ['areas' => [['name' => 'X']]])->assertForbidden();
    }
}
