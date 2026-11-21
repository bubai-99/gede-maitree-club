<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Family;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberPhotoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed([RoleSeeder::class, BookSeeder::class]);
        $family = Family::factory()->create();
        $this->member = Member::factory()->create(['family_id' => $family->id, 'name' => 'Ananya Sample', 'is_head' => true]);
        $this->user = User::factory()->forFamily($family)->create();
        $this->user->assignRole(Role::Member->value);
    }

    private function committee(Role $role): User
    {
        $user = User::factory()->committee()->create();
        $user->assignRole($role->value);

        return $user;
    }

    public function test_without_a_photo_the_card_shows_the_drawn_portrait(): void
    {
        $this->actingAs($this->user)->get('/app/card')->assertOk()->assertSee('No photo yet')->assertDontSee('/photo?v=', false);
    }

    public function test_the_family_adds_a_photo_and_it_is_on_the_card(): void
    {
        $this->actingAs($this->user)->post("/app/members/{$this->member->id}/photo", ['photo' => UploadedFile::fake()->image('me.jpg', 900, 1200)])
            ->assertSessionHas('status');

        $this->assertTrue($this->member->refresh()->hasPhoto());
        $this->actingAs($this->user)->get('/app/card')->assertSee((string) $this->member->photoUrl(), false);
        $this->actingAs($this->user)->get((string) $this->member->photoUrl())->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        $this->actingAs($this->user)->delete("/app/members/{$this->member->id}/photo")->assertSessionHas('status');
        $this->assertFalse($this->member->refresh()->hasPhoto());
    }

    public function test_only_images_are_accepted(): void
    {
        $this->actingAs($this->user)->post("/app/members/{$this->member->id}/photo", ['photo' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf')])
            ->assertSessionHasErrors('photo');
    }

    public function test_the_photo_is_private(): void
    {
        $this->actingAs($this->user)->post("/app/members/{$this->member->id}/photo", ['photo' => UploadedFile::fake()->image('me.jpg')]);
        $url = (string) $this->member->refresh()->photoUrl();

        $other = User::factory()->forFamily(Family::factory()->create())->create();
        $other->assignRole(Role::Member->value);
        $this->actingAs($other)->get($url)->assertForbidden();
        $this->actingAs($other)->post("/app/members/{$this->member->id}/photo", ['photo' => UploadedFile::fake()->image('x.jpg')])->assertForbidden();

        $this->actingAs($this->committee(Role::Volunteer))->get($url)->assertForbidden();
        $this->actingAs($this->committee(Role::Treasurer))->get($url)->assertOk();

        auth()->logout();
        $this->get($url)->assertRedirect();
    }
}
