<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CateringPackage;
use App\Models\Plant;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Everything on the nursery page the committee can change from the console:
 * plant lists, the members' discount, and the page text (hours, how to buy, review tags).
 */
class NurseryAdminTest extends TestCase
{
    use RefreshDatabase;

    private function as(Role $role): User
    {
        $this->seed([RoleSeeder::class, BookSeeder::class]);
        $user = User::factory()->committee()->create();
        $user->assignRole($role->value);
        $this->actingAs($user);

        return $user;
    }

    /**
     * @return array<string, list<array{key: string, name: string, name_bn?: string}>>
     */
    private function defaultLists(): array
    {
        return [
            'category' => [['key' => 'flowering', 'name' => 'Flowering'], ['key' => 'fruit', 'name' => 'Fruit']],
            'light' => [['key' => 'sun', 'name' => 'Full sun']],
            'water' => [['key' => 'often', 'name' => 'Water daily']],
        ];
    }

    public function test_the_website_editor_renames_and_adds_plant_types(): void
    {
        $this->as(Role::WebsiteEditor);
        Plant::factory()->create(['slug' => 'joba', 'name' => 'Joba', 'category' => 'flowering', 'light' => 'sun', 'water' => 'often', 'active' => 1]);

        $this->get('/admin/settings/plant-lists')->assertOk()->assertSee('Plant types');

        $lists = $this->defaultLists();
        $lists['category'][0]['name'] = 'Flowers all year';
        $lists['category'][0]['name_bn'] = 'সারা বছরের ফুল';
        $lists['category'][] = ['key' => '', 'name' => 'Bonsai'];
        $lists['light'][0]['name'] = 'Bright sun';
        $lists['water'][] = ['key' => '', 'name' => 'Every two or three days in summer'];

        $this->put('/admin/settings/plant-lists', $lists)
            ->assertRedirect(route('admin.plant-lists.edit'))->assertSessionHasNoErrors();

        // The plant keeps its key, so it shows under the new name
        $this->get('/nursery')->assertOk()
            ->assertSee('Flowers all year')
            ->assertSee('Bright sun')
            ->assertDontSee('Winter seasonal');
        $this->get('/admin/plants/create')->assertOk()->assertSee('value="bonsai"', false);

        // A new plant can use the new type; a type not in the list is refused
        $form = ['action' => 'publish', 'name' => 'Ficus', 'slug' => 'ficus', 'price' => 300, 'member_price' => 270, 'stock' => 2, 'active' => 1];
        $this->post('/admin/plants', [...$form, 'category' => 'bonsai'])->assertSessionHasNoErrors();
        $this->assertSame('bonsai', Plant::query()->where('slug', 'ficus')->value('category'));

        // A long name makes a long key; the plant still saves with it
        $this->put('/admin/plants/ficus', [...$form, 'category' => 'bonsai', 'water' => 'every-two-or-three-days-in-summer'])->assertSessionHasNoErrors();
        $this->assertSame('every-two-or-three-days-in-summer', Plant::query()->where('slug', 'ficus')->value('water'));
        $this->post('/admin/plants', [...$form, 'slug' => 'ficus-2', 'category' => 'seasonal'])->assertSessionHasErrors('category');
    }

    public function test_a_type_still_used_by_plants_cannot_be_removed(): void
    {
        $this->as(Role::WebsiteEditor);
        Plant::factory()->create(['slug' => 'aam', 'category' => 'fruit', 'light' => 'sun', 'water' => 'often']);

        $lists = $this->defaultLists();
        array_pop($lists['category']); // removes Fruit

        $this->put('/admin/settings/plant-lists', $lists)->assertSessionHasErrors('category');
        $this->assertNull(SiteSetting::query()->find('plant_lists'));

        // Once no plant uses it, it can go
        Plant::query()->where('slug', 'aam')->update(['category' => 'flowering']);
        $this->put('/admin/settings/plant-lists', $lists)->assertSessionHasNoErrors();
        $this->get('/nursery')->assertDontSee('?category=fruit', false);
    }

    public function test_the_members_discount_is_set_in_settings(): void
    {
        $this->as(Role::Admin);
        $package = CateringPackage::factory()->create(['rate' => 200]);
        $this->assertSame(180, $package->memberRate()); // 10% from config/club.php

        $this->put('/admin/settings', [
            'contact' => ['address' => '1 New Road', 'phone' => '+91 90000 00000', 'whatsapp' => '919000000000', 'member_discount_percent' => '15'],
        ])->assertSessionHasNoErrors();

        $this->assertSame(170, $package->fresh()->memberRate());
        $this->get('/nursery')->assertSee('Members 15% off');
        $this->get('/terms')->assertSee('Members get 15% off');

        $this->put('/admin/settings', ['contact' => ['address' => '1 New Road', 'phone' => '+91 90000 00000', 'whatsapp' => '919000000000', 'member_discount_percent' => '120']])
            ->assertSessionHasErrors('contact.member_discount_percent');
    }

    public function test_the_nursery_hours_steps_and_review_tags_are_page_text(): void
    {
        $this->as(Role::WebsiteEditor);

        $this->get('/nursery')->assertSee('7–10 am &amp; 5–8 pm', false)->assertSee('Sutapa-di sets them aside and replies.');

        $this->put('/admin/pages/nursery', ['action' => 'publish', 'fields' => [
            'hours' => '6–11 am only',
            'step2_text' => 'Rina-di keeps them aside and replies.',
            'review_tags' => 'Strong roots, Good pots',
        ]])->assertSessionHasNoErrors();

        $this->get('/nursery')->assertOk()
            ->assertSee('6–11 am only')
            ->assertSee('Rina-di keeps them aside and replies.')
            ->assertDontSee('Sutapa-di');
    }
}
