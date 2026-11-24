<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The club's place (config/club.php, from its Google Maps page): the Contact page's map and
 * directions, the address and phone, and the location search engines read.
 */
class ClubLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_contact_page_shows_the_club_on_the_map(): void
    {
        $this->get('/contact')->assertOk()
            ->assertSee('https://www.google.com/maps?q=23.4943573,88.788812&amp;z=16', false)
            ->assertSee('https://www.google.com/maps/dir/?api=1&amp;destination=23.4943573,88.788812', false)
            ->assertSee('https://maps.google.com/?cid=460540838906069322', false)
            ->assertSee('BSF Ground, Banpur–Gede Road, Gede Uttarpara, Gede, Nadia, West Bengal 741503')
            ->assertSee('href="tel:+919093524255"', false)
            ->assertDontSee('Netaji Subhas Road');
    }

    public function test_search_engines_get_the_address_and_map_position(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('"postalCode":"741503"', $html);
        $this->assertStringContainsString('"latitude":23.4943573', $html);
        $this->assertStringContainsString('"longitude":88.788812', $html);
    }
}
