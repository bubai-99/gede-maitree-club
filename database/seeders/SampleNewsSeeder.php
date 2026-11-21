<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Models\NewsCategory;
use App\Models\NewsPost;
use Illuminate\Database\Seeder;

/**
 * SAMPLE news categories and stories (photos are the sample photos from config/photos.php), so
 * the News page has something to show. The committee replaces them with the club's own.
 */
class SampleNewsSeeder extends Seeder
{
    public function run(): void
    {
        if (NewsCategory::query()->exists()) {
            return;
        }

        $cats = collect([
            ['pujas', 'Pujas', 'পূজা', 1],
            ['sports', 'Sports', 'খেলা', 2],
            ['service', 'Service', 'সেবা', 3],
            ['club-life', 'Club life', 'ক্লাবের কথা', 4],
        ])->mapWithKeys(fn (array $c): array => [$c[0] => NewsCategory::query()->create(['slug' => $c[0], 'name' => $c[1], 'name_bn' => $c[2], 'sort' => $c[3]])]);

        $posts = [
            ['durga-puja-2026', 'pujas', 'pujaCrowd', true, 35,
                'Durga Puja 2026: 4,200 plates of bhog and 61 volunteers', 'দুর্গাপূজা ২০২৬: ৪,২০০ থালা ভোগ আর ৬১ জন স্বেচ্ছাসেবক',
                'Five days, one pandal and the whole para. How the serving team kept the bhog lines moving, and where the chanda went.', 'পাঁচ দিন, একটি মণ্ডপ আর গোটা পাড়া। পরিবেশন দল কীভাবে ভোগের লাইন চালু রাখল, আর চাঁদার টাকা কোথায় গেল।',
                "The pandal went up on the para ground a week before Mahalaya, built by the same decorators as last year.\n\n## Bhog for 4,200\n\nOn Ashtami the serving team fed 4,200 people in four batches. Every batch had a team leader, and the tables were cleared and laid again between batches.\n\n- 61 volunteers on duty across five days\n- Bhog counters at both gates\n- Separate utensils for the elderly and children\n\n> We stood in the queue for twenty minutes and nobody pushed. That is Netaji Para. — a member from Lane 3\n\nThe full accounts go up on the Chanda & accounts page after the audit.",
                "মহালয়ার এক সপ্তাহ আগে পাড়ার মাঠে মণ্ডপ উঠল।\n\n## ৪,২০০ জনের ভোগ\n\nঅষ্টমীতে পরিবেশন দল চার দফায় ৪,২০০ জনকে খাওয়াল।"],
            ['sports-day-entries', 'sports', 'race', false, 12,
                'Sports Day entries are open: sack race, relay and the parents’ tug of war', 'ক্রীড়া দিবসের নাম লেখানো শুরু',
                'Register children and adults in the member app before 7 December. New this year: an under-8 sack race.', 'সদস্য অ্যাপে ৭ ডিসেম্বরের আগে নাম লেখান। এবছর নতুন: ৮ বছরের কমবয়সীদের বস্তা দৌড়।',
                "Sports Day is on 13 December on the Netaji Para ground, from 7 am.\n\n## Events\n\n1. Under-8 and under-12 sack race\n2. 4 × 100 m relay\n3. Parents’ tug of war\n\nEntries close on 7 December. Families register in the member app; each family gets one pass.",
                null],
            ['blood-camp-58-donors', 'service', 'blood', false, 50,
                'Blood donation camp: 58 donors in one morning', 'রক্তদান শিবির: এক সকালে ৫৮ জন দাতা',
                'The club room turned into a camp with the district blood bank. Thank you to every donor, and to the volunteers who served tea.', 'জেলা ব্লাড ব্যাঙ্কের সঙ্গে ক্লাব ঘরে শিবির। প্রত্যেক দাতা আর চা পরিবেশন করা স্বেচ্ছাসেবকদের ধন্যবাদ।',
                "Fifty-eight donors gave blood at the club room. The next camp is on 28 November.\n\nIf you would like to be on the club’s list of donors, tell the secretary your blood group.",
                null],
            ['winter-flowers-at-the-nursery', 'club-life', 'potted', false, 6,
                'Winter flowers arrive at the club nursery', 'ক্লাবের নার্সারিতে শীতের ফুল',
                'Dahlia, chrysanthemum and marigold seedlings are in. Members pay 10% less.', 'ডালিয়া, চন্দ্রমল্লিকা আর গাঁদার চারা এসেছে। সদস্যদের ১০% ছাড়।',
                "The winter batch is in at the nursery by the club gate: dahlia, chrysanthemum and marigold trays.\n\nAsk at the counter how much sun and water each one needs.",
                null],
        ];

        foreach ($posts as [$slug, $cat, $photo, $featured, $daysAgo, $title, $titleBn, $summary, $summaryBn, $body, $bodyBn]) {
            NewsPost::query()->create([
                'slug' => $slug,
                'category_id' => $cats[$cat]->id,
                'photo' => $photo,
                'featured' => $featured,
                'title' => $title,
                'title_bn' => $titleBn,
                'summary' => $summary,
                'summary_bn' => $summaryBn,
                'body' => $body,
                'body_bn' => $bodyBn,
                'status' => PublishStatus::Published,
                'published_at' => now()->subDays($daysAgo),
            ]);
        }
    }
}
