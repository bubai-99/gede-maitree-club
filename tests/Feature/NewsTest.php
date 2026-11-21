<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Enums\Role;
use App\Models\NewsCategory;
use App\Models\NewsPost;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private NewsCategory $pujas;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed([RoleSeeder::class, BookSeeder::class]);
        $this->editor = User::factory()->committee()->create();
        $this->editor->assignRole(Role::WebsiteEditor->value);
        $this->pujas = NewsCategory::query()->create(['slug' => 'pujas', 'name' => 'Pujas', 'name_bn' => 'পূজা']);
    }

    /**
     * @return array<string, mixed>
     */
    private function story(array $overrides = []): array
    {
        return [
            'action' => 'publish',
            'title' => 'Durga Puja 2026',
            'slug' => 'durga-puja-2026',
            'category_id' => $this->pujas->id,
            'summary' => 'Five days and the whole para.',
            'body' => "The pandal went up.\n\nhttps://youtu.be/dQw4w9WgXcQ\n\n**Bhog** for 4,200.",
            'videos' => "https://www.facebook.com/gedemaitreeclub/videos/1234567890/ | Bijoya\nhttps://vimeo.com/76979871",
            ...$overrides,
        ];
    }

    public function test_an_editor_publishes_a_story_with_videos_and_photos(): void
    {
        $this->actingAs($this->editor)->post('/admin/news', $this->story([
            'photo_upload' => UploadedFile::fake()->image('cover.jpg', 1600, 1000),
            'images_upload' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ]))->assertRedirect('/admin/news');

        $post = NewsPost::query()->sole();
        $this->assertTrue($post->isPublished());
        $this->assertSame([['url' => 'https://www.facebook.com/gedemaitreeclub/videos/1234567890/', 'caption' => 'Bijoya'], ['url' => 'https://vimeo.com/76979871', 'caption' => null]], $post->videos);
        $this->assertCount(2, $post->getMedia('images'));
        $this->assertTrue($post->hasUploadedPhoto());

        $page = $this->get('/news/durga-puja-2026')->assertOk()
            ->assertSee('Durga Puja 2026')
            ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0', false)
            ->assertSee('https://www.facebook.com/plugins/video.php', false)
            ->assertSee('https://player.vimeo.com/video/76979871', false)
            ->assertSee('<strong>Bhog</strong>', false)
            ->assertSee('Bijoya');
        $this->assertStringNotContainsString('<p>https://youtu.be', $page->getContent());

        // The console opens, unpublishes and deletes an existing story
        $this->actingAs($this->editor)->get("/admin/news/{$post->slug}/edit")->assertOk()->assertSee('Durga Puja 2026');
        $this->actingAs($this->editor)->delete("/admin/news/{$post->slug}/publish")->assertRedirect();
        $this->assertFalse($post->refresh()->isPublished());
        $this->actingAs($this->editor)->post("/admin/news/{$post->slug}/publish")->assertRedirect();

        // Remove one photo
        $first = $post->getMedia('images')->first();
        $this->actingAs($this->editor)->put("/admin/news/{$post->slug}", $this->story(['remove_images' => [$first?->id]]))->assertRedirect();
        $this->assertCount(1, $post->refresh()->getMedia('images'));
    }

    public function test_html_typed_into_a_story_is_removed(): void
    {
        $this->actingAs($this->editor)->post('/admin/news', $this->story(['body' => "Hello <script>alert(1)</script> <iframe src=\"https://evil.example\"></iframe>\n\n[x](javascript:alert(1))"]));

        $html = $this->get('/news/durga-puja-2026')->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringNotContainsString('evil.example', $html);
        $this->assertStringNotContainsString('javascript:alert', $html);
    }

    public function test_a_line_that_is_not_a_link_is_refused(): void
    {
        $this->actingAs($this->editor)->post('/admin/news', $this->story(['videos' => 'see the facebook page']))->assertSessionHasErrors('videos');
        $this->assertSame(0, NewsPost::query()->count());
    }

    public function test_the_list_filters_by_category_and_hides_drafts(): void
    {
        $sports = NewsCategory::query()->create(['slug' => 'sports', 'name' => 'Sports']);
        NewsPost::query()->create(['slug' => 'a', 'title' => 'Puja story', 'summary' => 's', 'category_id' => $this->pujas->id, 'status' => PublishStatus::Published, 'published_at' => now()]);
        NewsPost::query()->create(['slug' => 'b', 'title' => 'Sports story', 'summary' => 's', 'category_id' => $sports->id, 'status' => PublishStatus::Published, 'published_at' => now()->subDay()]);
        NewsPost::query()->create(['slug' => 'c', 'title' => 'Draft story', 'summary' => 's', 'category_id' => $sports->id, 'status' => PublishStatus::Draft]);

        $this->get('/news')->assertOk()->assertSee('Puja story')->assertSee('Sports story')->assertDontSee('Draft story');
        $this->get('/news?category=sports')->assertOk()->assertSee('Sports story')->assertDontSee('Puja story');
        $this->get('/news?category=nope')->assertNotFound();
        $this->get('/news/c')->assertNotFound();
        $this->get('/sitemap.xml')->assertSee(route('news.show', 'a'))->assertDontSee(route('news.show', 'c'));
    }

    public function test_categories_are_managed_by_editors_only(): void
    {
        $this->actingAs($this->editor)->post('/admin/news-categories', ['name' => 'Service', 'name_bn' => 'সেবা', 'sort' => 3])->assertSessionHas('status');
        $this->assertTrue(NewsCategory::query()->where('slug', 'service')->exists());

        $treasurer = User::factory()->committee()->create();
        $treasurer->assignRole(Role::Treasurer->value);
        $this->actingAs($treasurer)->get('/admin/news')->assertOk();
        $this->actingAs($treasurer)->post('/admin/news', $this->story())->assertForbidden();
        $this->actingAs($treasurer)->post('/admin/news-categories', ['name' => 'X'])->assertForbidden();
    }
}
