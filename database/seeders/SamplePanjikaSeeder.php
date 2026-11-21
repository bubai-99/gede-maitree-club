<?php

namespace Database\Seeders;

use App\Enums\PanjikaKind;
use App\Models\Event;
use App\Models\PanjikaDay;
use Illuminate\Database\Seeder;

/**
 * SAMPLE puja days, parbon and wedding days (with lagna) from the prototype, so the Panjika page
 * has something to show. The committee replaces them with the dates from the club's panjika.
 */
class SamplePanjikaSeeder extends Seeder
{
    public function run(): void
    {
        if (PanjikaDay::query()->exists()) {
            return;
        }

        $saraswati = Event::query()->where('slug', 'saraswati')->value('id');

        $days = [
            ['2026-11-19', PanjikaKind::Puja, 'Jagaddhatri Puja', 'জগদ্ধাত্রী পূজা', 8],
            ['2026-11-24', PanjikaKind::Puja, 'Rash Purnima', 'রাসপূর্ণিমা', 14],
            ['2027-01-14', PanjikaKind::Parbon, 'Poush Sankranti · pithe-puli', 'পৌষ সংক্রান্তি · পিঠে-পুলি', null],
            ['2027-02-11', PanjikaKind::Puja, 'Saraswati Puja', 'সরস্বতী পূজা', 4, $saraswati],
            ['2027-03-22', PanjikaKind::Puja, 'Dol Purnima', 'দোলপূর্ণিমা', 14],
            ['2027-04-14', PanjikaKind::Parbon, 'Choitro Sankranti · Charak', 'চৈত্র সংক্রান্তি · চড়ক', null],
            ['2027-04-15', PanjikaKind::Parbon, 'Poila Boishakh · Bengali New Year 1434', 'পয়লা বৈশাখ · নববর্ষ ১৪৩৪', null],
        ];
        foreach ($days as $row) {
            [$date, $kind, $name, $nameBn, $tithi] = $row;
            PanjikaDay::query()->create(['date' => $date, 'kind' => $kind, 'name' => $name, 'name_bn' => $nameBn, 'tithi' => $tithi, 'event_id' => $row[5] ?? null]);
        }

        $weddings = [
            ['2026-11-22', '8:10 pm – 1:40 am', 'রাত ৮:১০ – ১:৪০'],
            ['2026-11-27', '7:25 pm – 11:50 pm', 'সন্ধ্যা ৭:২৫ – রাত ১১:৫০'],
            ['2026-12-03', '9:05 pm – 2:20 am', 'রাত ৯:০৫ – ২:২০'],
            ['2026-12-07', '6:50 pm – 10:30 pm', 'সন্ধ্যা ৬:৫০ – রাত ১০:৩০'],
            ['2027-01-22', '8:40 pm – 3:10 am', 'রাত ৮:৪০ – ৩:১০'],
            ['2027-01-28', '7:55 pm – 12:30 am', 'সন্ধ্যা ৭:৫৫ – রাত ১২:৩০'],
            ['2027-02-04', '9:30 pm – 4:00 am', 'রাত ৯:৩০ – ভোর ৪:০০'],
            ['2027-02-19', '8:15 pm – 1:05 am', 'রাত ৮:১৫ – ১:০৫'],
            ['2027-03-03', '7:40 pm – 11:15 pm', 'সন্ধ্যা ৭:৪০ – রাত ১১:১৫'],
        ];
        foreach ($weddings as [$date, $lagna, $lagnaBn]) {
            PanjikaDay::query()->create(['date' => $date, 'kind' => PanjikaKind::Wedding, 'name' => 'Wedding day', 'name_bn' => 'বিয়ের দিন', 'lagna' => $lagna, 'lagna_bn' => $lagnaBn]);
        }
    }
}
