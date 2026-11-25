<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pages whose filters swap in place (resources/js/instant.js) mark that part, and the swapped-in
 * page is the same page: a filtered address still works on its own (shared or reloaded).
 */
class InstantFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_website_lists_mark_the_part_that_filters_in_place(): void
    {
        foreach (['/gallery' => 'gallery-page', '/news' => 'news-page', '/notices' => 'notices-page'] as $path => $id) {
            $this->get($path)->assertOk()->assertSee('id="'.$id.'" data-instant', false);
        }
    }

    public function test_a_filtered_address_works_on_its_own(): void
    {
        SiteSetting::query()->create(['key' => 'gallery', 'value' => [
            'albums' => [['key' => 'puja', 'title' => 'Pujas']],
            'days' => [['id' => 'durga-26', 'date' => '2026-10-19', 'album' => 'puja', 'title' => 'Durga Puja', 'items' => [['type' => 'photo', 'photo' => 'pujaCrowd'], ['type' => 'photo', 'photo' => 'pujaNight']]]],
        ]]);

        $this->get('/gallery?type=photo')->assertOk()
            ->assertSee('id="gallery-page" data-instant', false)
            ->assertSee('data-lightbox="gallery-photos"', false)
            ->assertSee('data-scroll-fade', false);
        $this->get('/gallery?type=video')->assertOk();
        // The album page: the big photo opens full screen, with the album's other photos as next
        $this->get('/gallery?open=durga-26&i=0')->assertOk()->assertSee('data-lightbox="album-photos" data-order="1"', false);
    }
}
