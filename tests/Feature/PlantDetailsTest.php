<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Plant;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlantDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_website_editor_adds_a_care_guide_videos_and_more_photos(): void
    {
        Storage::fake('public');
        $this->seed([RoleSeeder::class, BookSeeder::class]);
        $editor = User::factory()->committee()->create();
        $editor->assignRole(Role::WebsiteEditor->value);
        $plant = Plant::factory()->create(['slug' => 'sample-tulsi', 'name' => 'Sample Tulsi', 'category' => 'medicinal', 'stock' => 5]);

        $this->actingAs($editor)->put("/admin/plants/{$plant->slug}", [
            'action' => 'publish', 'name' => 'Sample Tulsi', 'slug' => 'sample-tulsi', 'category' => 'medicinal', 'price' => 50, 'member_price' => 45, 'stock' => 5, 'active' => 1,
            'details' => "## Watering\nEvery other day.\n\n<script>alert(1)</script>",
            'videos' => 'https://youtu.be/dQw4w9WgXcQ | Repotting a tulsi',
            'images_upload' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $plant->refresh();
        $this->assertSame([['url' => 'https://youtu.be/dQw4w9WgXcQ', 'caption' => 'Repotting a tulsi']], $plant->videos);
        $this->assertCount(2, $plant->getMedia('images'));
        $this->assertCount(2, $plant->photos()); // no main photo on this plant, so the two uploads

        $html = $this->get('/nursery')->assertOk()->getContent();
        $this->assertStringContainsString('Watering', $html);
        $this->assertStringContainsString('youtube-nocookie.com\/embed\/dQw4w9WgXcQ', $html);
        $this->assertStringNotContainsString('<script>alert(1)', $html);

        $this->actingAs($editor)->put("/admin/plants/{$plant->slug}", ['action' => 'publish', 'name' => 'Sample Tulsi', 'slug' => 'sample-tulsi', 'category' => 'medicinal', 'price' => 50, 'member_price' => 45, 'stock' => 5, 'videos' => 'not a link'])
            ->assertSessionHasErrors('videos');
    }
}
