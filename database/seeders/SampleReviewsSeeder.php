<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Enums\ReviewKind;
use App\Models\Review;
use Illuminate\Database\Seeder;

/**
 * SAMPLE reviews from the prototype, so the catering and nursery pages show the reviews section.
 * The committee replaces them with real ones, each with the customer's permission.
 */
class SampleReviewsSeeder extends Seeder
{
    public function run(): void
    {
        if (Review::query()->exists()) {
            return;
        }

        $rows = [
            [ReviewKind::Nursery, 5, '2026-09-10', 'The tulsi and the lemon plant are doing well. They told me exactly how much to water, and the member price helps.', 'তুলসী আর লেবু গাছ ভালো আছে। কতটা জল দিতে হবে ঠিক বলে দিয়েছিল, সদস্য মূল্যেও সুবিধা।', 'A gardener at Pukur Par', 'পুকুর পাড়ের এক বাগানপ্রেমী', 'Tulsi and lemon', 'তুলসী ও লেবু'],
            [ReviewKind::Nursery, 5, '2026-10-12', 'Ordered marigold trays on WhatsApp for the puja. They were ready at the gate the next morning, healthy and well potted.', 'পূজার জন্য হোয়াটসঅ্যাপে গাঁদার ট্রে চেয়েছিলাম। পরদিন সকালে গেটে তৈরি, চারা সতেজ, ভালো টবে।', 'A member in Lane 1', '১ নং গলির এক সদস্য', 'Marigold seedling trays', 'গাঁদার চারার ট্রে'],
            [ReviewKind::Nursery, 4, '2026-01-20', 'Good plants at fair prices, and the money goes to the club. The dahlia I bought last winter flowered for weeks.', 'ন্যায্য দামে ভালো গাছ, আর টাকা ক্লাবের কাজে যায়। গত শীতে কেনা ডালিয়ায় কয়েক সপ্তাহ ধরে ফুল।', 'A neighbour on School Road', 'স্কুল রোডের এক প্রতিবেশী', 'Dahlia', 'ডালিয়া'],
            [ReviewKind::Nursery, 5, '2026-07-05', 'My snake plant and money plant are thriving in a dark flat. They picked plants that suit low light.', 'অন্ধকার ফ্ল্যাটেও স্নেক প্ল্যান্ট আর মানি প্ল্যান্ট দারুণ আছে। কম আলোর উপযুক্ত গাছই বেছে দিয়েছিলেন।', 'A new tenant in Lane 3', '৩ নং গলির এক নতুন ভাড়াটে', 'Indoor plants', 'ঘরের গাছ'],
            [ReviewKind::Catering, 5, '2026-02-14', 'Two hundred and twenty guests at our daughter’s wedding and not one plate was late. The team leader looked after the elders himself.', 'মেয়ের বিয়েতে ২২০ জন অতিথি, একটা পাতও দেরিতে যায়নি। দলনেতা নিজে বয়স্কদের দেখাশোনা করেছেন।', 'A family on School Road', 'স্কুল রোডের এক পরিবার', 'Full setup · wedding, 220 guests', 'সম্পূর্ণ ব্যবস্থা · বিয়ে, ২২০ জন'],
            [ReviewKind::Catering, 5, '2026-08-09', 'They came an hour early, laid the banana leaves and glasses, and washed up after. We only had to think about the food.', 'এক ঘণ্টা আগে এসে কলাপাতা আর গ্লাস সাজিয়েছেন, পরে ধোয়ামোছাও। আমাদের শুধু খাবারের কথা ভাবতে হয়েছে।', 'A family in Lane 2', '২ নং গলির এক পরিবার', 'Team + tableware · annaprashan, 70 guests', 'দল + থালাবাসন · অন্নপ্রাশন, ৭০ জন'],
            [ReviewKind::Catering, 4, '2026-06-18', 'Quiet, respectful service at my father’s shraddha. The quote was clear and the final bill matched the guests who came.', 'বাবার শ্রাদ্ধে শান্ত, সম্মানের সঙ্গে পরিবেশন। দাম পরিষ্কার জানিয়েছিল, শেষ বিলও আসা অতিথি অনুযায়ী।', 'A member at Pukur Par', 'পুকুর পাড়ের এক সদস্য', 'Serving team · shraddha, 60 guests', 'পরিবেশন দল · শ্রাদ্ধ, ৬০ জন'],
            [ReviewKind::Catering, 5, '2026-09-22', 'Our office lunch for 90 went smoothly. Two servers kept the counters full and the tables clear the whole time.', '৯০ জনের অফিসের দুপুরের খাওয়া নির্বিঘ্নে হয়েছে। দুজন পরিবেশনকারী সারাক্ষণ কাউন্টার ভরা আর টেবিল পরিষ্কার রেখেছেন।', 'A shop owner on the main road', 'বড় রাস্তার এক দোকানদার', 'Serving team · staff lunch, 90 guests', 'পরিবেশন দল · কর্মীদের খাওয়া, ৯০ জন'],
        ];

        foreach ($rows as $i => [$kind, $rating, $date, $body, $bodyBn, $who, $whoBn, $what, $whatBn]) {
            Review::query()->create([
                'kind' => $kind, 'rating' => $rating, 'reviewed_on' => $date, 'sort' => $i,
                'body' => $body, 'body_bn' => $bodyBn, 'who' => $who, 'who_bn' => $whoBn, 'what' => $what, 'what_bn' => $whatBn,
                'status' => PublishStatus::Published, 'published_at' => now(),
            ]);
        }
    }
}
