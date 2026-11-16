<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocaleTest extends TestCase
{
    public function test_the_site_is_in_english_by_default(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('Twelve years of pujas, play and looking out for each other');
    }

    public function test_switching_to_bengali_is_remembered(): void
    {
        $this->from('/')->get('/lang/bn')
            ->assertRedirect('/')
            ->assertSessionHas('locale', 'bn')
            ->assertCookie('locale', 'bn');

        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="bn">', false)
            ->assertSee('বারো বছরের পূজা, খেলা আর পাশে থাকা');
    }

    public function test_switching_back_to_english(): void
    {
        $this->withSession(['locale' => 'bn'])->from('/')->get('/lang/en')->assertRedirect('/');

        $this->get('/')->assertSee('<html lang="en">', false);
    }

    public function test_only_supported_languages_are_accepted(): void
    {
        $this->get('/lang/fr')->assertNotFound();
    }

    public function test_the_lang_query_string_selects_the_language(): void
    {
        $this->get('/?lang=bn')->assertOk()->assertSee('<html lang="bn">', false);
    }

    public function test_an_unknown_lang_query_string_is_ignored(): void
    {
        $this->get('/?lang=xx')->assertOk()->assertSee('<html lang="en">', false);
    }

    public function test_switching_never_redirects_to_another_site(): void
    {
        $this->get('/lang/bn', ['referer' => 'https://evil.example.com/page'])
            ->assertRedirect(url('/'));
    }

    public function test_layout_has_seo_basics(): void
    {
        $this->get('/')
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('hreflang="bn"', false)
            ->assertSee('"@type":"NGO"', false)
            ->assertSee('property="og:title"', false);
    }
}
