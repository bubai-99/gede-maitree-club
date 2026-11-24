<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The catering page's How booking works steps, occasions and food note come from
 * Website → Pages → Serving.
 */
class CateringPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_defaults_show_with_an_icon_for_each_occasion(): void
    {
        $this->get('/catering')->assertOk()
            ->assertSee('How booking works')
            ->assertSee('Book with 20% advance')
            ->assertSee('Shraddha &amp; niyombhongo', false)
            ->assertSee('Vegetarian and shraddha meals are served with separate utensils and a separate team.');
    }

    public function test_the_website_editor_changes_the_steps_and_occasions(): void
    {
        $this->seed([RoleSeeder::class, BookSeeder::class]);
        $editor = User::factory()->committee()->create();
        $editor->assignRole(Role::WebsiteEditor->value);

        $this->actingAs($editor)->put('/admin/pages/catering', ['action' => 'publish', 'fields' => [
            'step3_title' => 'Book with 25% advance',
            'occasions' => "Wedding\nSaraswati Puja bhog\nHousewarming",
            'food_note' => 'Separate team for vegetarian meals.',
        ]])->assertSessionHasNoErrors();

        $html = $this->get('/catering')->assertOk()->getContent();
        $this->assertStringContainsString('Book with 25% advance', $html);
        $this->assertStringNotContainsString('Book with 20% advance', $html);
        // The occasions section only (the quote form has its own list of occasions)
        $occasions = (string) strstr((string) strstr($html, 'id="occasions"'), '</section>', true);
        $this->assertStringContainsString('Saraswati Puja bhog', $occasions);
        $this->assertStringContainsString('Housewarming', $occasions);
        $this->assertStringNotContainsString('Annaprashan', $occasions);
        $this->assertStringContainsString('Separate team for vegetarian meals.', $html);
        // Known words pick their icon; a new occasion gets the general one
        $this->assertMatchesRegularExpression('/data-lucide="flame"[^<]*<\/i>\s*<\/span>\s*<span[^>]*>Saraswati Puja bhog/', $html);
        $this->assertMatchesRegularExpression('/data-lucide="sparkles"[^<]*<\/i>\s*<\/span>\s*<span[^>]*>Housewarming/', $html);
    }
}
