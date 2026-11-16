<?php

namespace Tests\Unit;

use App\Support\Bangla;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class BanglaTest extends TestCase
{
    public function test_digits_are_bengali_only_on_the_bengali_site(): void
    {
        App::setLocale('en');
        $this->assertSame('2014', Bangla::digits(2014));

        App::setLocale('bn');
        $this->assertSame('২০১৪', Bangla::digits(2014));
        $this->assertSame('MC-০১০৪', Bangla::digits('MC-0104'));
        $this->assertSame('২০১৪', digits('2014'));
    }

    public function test_rupees_use_indian_grouping(): void
    {
        App::setLocale('en');
        $this->assertSame('₹1,23,456', Bangla::rupees(123456));
        $this->assertSame('₹100', rupees(100));

        App::setLocale('bn');
        $this->assertSame('₹১,২০০', Bangla::rupees(1200));
    }

    public function test_dates_are_localised(): void
    {
        $date = CarbonImmutable::parse('2026-11-28');

        App::setLocale('en');
        $this->assertSame('28 November 2026', Bangla::date($date));

        App::setLocale('bn');
        $this->assertSame('২৮ নভেম্বর ২০২৬', Bangla::date($date));
        $this->assertSame('', Bangla::date(null));
    }
}
