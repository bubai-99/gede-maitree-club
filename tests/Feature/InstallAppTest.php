<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The member app can be installed from the website; each app keeps to its own part of the site.
 */
class InstallAppTest extends TestCase
{
    public function test_website_pages_offer_the_member_app(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('<link rel="manifest" href="'.asset('member.webmanifest').'">', false)
            ->assertSee('<link rel="apple-touch-icon" href="'.asset('icons/member-180.png').'">', false)
            ->assertSee('Get the app');
    }

    public function test_each_app_keeps_to_its_own_section(): void
    {
        $member = json_decode((string) file_get_contents(public_path('member.webmanifest')), true);
        $admin = json_decode((string) file_get_contents(public_path('admin.webmanifest')), true);

        $this->assertSame(['/app', '/app'], [$member['start_url'], $member['scope']]);
        $this->assertSame(['/admin', '/admin'], [$admin['start_url'], $admin['scope']]);
        $this->assertSame('standalone', $member['display']);
        $this->assertContains('512x512', array_column($member['icons'], 'sizes'));
    }
}
