<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Enums\ReviewKind;
use App\Enums\Role;
use App\Models\Plant;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, BookSeeder::class]);
    }

    private function review(ReviewKind $kind, int $rating, string $body, PublishStatus $status = PublishStatus::Published): Review
    {
        return Review::query()->create(['kind' => $kind, 'rating' => $rating, 'body' => $body, 'who' => 'A sample neighbour', 'status' => $status, 'published_at' => $status === PublishStatus::Published ? now() : null]);
    }

    public function test_published_reviews_show_with_their_average_on_the_right_page(): void
    {
        $this->review(ReviewKind::Nursery, 5, 'Lovely tulsi.');
        $this->review(ReviewKind::Nursery, 4, 'Good marigolds.');
        $this->review(ReviewKind::Nursery, 1, 'A draft review.', PublishStatus::Draft);
        $this->review(ReviewKind::Catering, 5, 'Great serving team.');

        $this->get('/nursery')->assertOk()->assertSee('What gardeners say')->assertSee('4.5')->assertSee('Lovely tulsi.')
            ->assertDontSee('A draft review.')->assertDontSee('Great serving team.');
        $this->get('/catering')->assertOk()->assertSee('What families say')->assertSee('Great serving team.');
    }

    public function test_no_reviews_no_section(): void
    {
        $this->get('/nursery')->assertOk()->assertDontSee('What gardeners say');
    }

    public function test_the_website_editor_adds_a_review_and_others_only_look(): void
    {
        $editor = User::factory()->committee()->create();
        $editor->assignRole(Role::WebsiteEditor->value);
        $this->actingAs($editor)->post('/admin/reviews', ['action' => 'publish', 'kind' => 'nursery', 'rating' => 5, 'body' => 'Healthy plants.', 'who' => 'A gardener'])->assertRedirect('/admin/reviews');
        $review = Review::query()->sole();
        $this->assertTrue($review->isPublished());
        $this->actingAs($editor)->get("/admin/reviews/{$review->id}/edit")->assertOk()->assertSee('Healthy plants.');
        $this->actingAs($editor)->post('/admin/reviews', ['action' => 'publish', 'kind' => 'nursery', 'rating' => 9, 'body' => '', 'who' => ''])->assertSessionHasErrors(['rating', 'body', 'who']);

        $treasurer = User::factory()->committee()->create();
        $treasurer->assignRole(Role::Treasurer->value);
        $this->actingAs($treasurer)->get('/admin/reviews')->assertOk();
        $this->actingAs($treasurer)->post('/admin/reviews', ['action' => 'publish', 'kind' => 'nursery', 'rating' => 5, 'body' => 'X', 'who' => 'Y'])->assertForbidden();
    }

    public function test_every_plant_is_on_the_page_so_filtering_needs_no_reload(): void
    {
        Plant::factory()->create(['name' => 'Sample Lemon', 'category' => 'fruit', 'about' => 'A sour lemon.', 'tip' => 'Sun.', 'stock' => 5]);
        Plant::factory()->create(['name' => 'Sample Snake', 'category' => 'indoor', 'stock' => 5]);

        $this->get('/nursery?category=fruit')->assertOk()->assertSee('Sample Lemon')->assertSee('Sample Snake')->assertSee('A sour lemon.', false)->assertSee('"category":"fruit"', false);
    }
}
