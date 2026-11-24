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
            ->assertSee('<link rel="apple-touch-icon" href="'.asset('icons/member-180.png?v=2').'">', false)
            ->assertSee('Get the app');
    }

    public function test_each_app_keeps_to_its_own_section(): void
    {
        $member = json_decode((string) file_get_contents(public_path('member.webmanifest')), true);
        $admin = json_decode((string) file_get_contents(public_path('admin.webmanifest')), true);

        // The member app covers the website too ("Open in app" on any page once installed); the console keeps to /admin
        $this->assertSame(['/app', '/'], [$member['start_url'], $member['scope']]);
        $this->assertSame(['/admin', '/admin'], [$admin['start_url'], $admin['scope']]);
        $this->assertSame('standalone', $member['display']);
        // Screenshots for the install dialog: wide (computers) and narrow (phones), all present at their stated size
        $this->assertEqualsCanonicalizing(['narrow', 'wide'], array_values(array_unique(array_column($member['screenshots'], 'form_factor'))));
        foreach ($member['screenshots'] as $shot) {
            $file = public_path(ltrim($shot['src'], '/'));
            $this->assertFileExists($file);
            $this->assertSame($shot['sizes'], implode('x', array_slice((array) getimagesize($file), 0, 2)));
        }
        // A standard and a maskable (Android crops it to its own shape) icon at 192 and 512, all present
        foreach ([$member, $admin] as $manifest) {
            $this->assertEqualsCanonicalizing(['any 192x192', 'any 512x512', 'maskable 192x192', 'maskable 512x512'],
                array_map(fn ($i) => $i['purpose'].' '.$i['sizes'], $manifest['icons']));
            foreach ($manifest['icons'] as $icon) {
                $file = public_path(ltrim((string) strtok($icon['src'], '?'), '/'));
                $this->assertFileExists($file);
                $this->assertSame($icon['sizes'], implode('x', array_slice((array) getimagesize($file), 0, 2)));
            }
        }
    }
}
