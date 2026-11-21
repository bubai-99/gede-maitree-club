<?php

namespace Database\Seeders;

use App\Models\Plant;
use Illuminate\Database\Seeder;

/**
 * SAMPLE care guides for the sample plants (one per kind of plant) and the nursery's own sample
 * photos as extra gallery shots, so the plant details panel has something to show. The committee
 * writes the real guides, uploads photos and adds videos in the console (Website → Plants).
 */
class SamplePlantDetailsSeeder extends Seeder
{
    /** @var array<string, array{0: string, 1: string}> */
    private const GUIDES = [
        'flowering' => [
            "## Where to keep it\nA sunny balcony or rooftop: at least five hours of sun a day for steady flowers.\n\n## Watering\nWater when the top inch of soil is dry; every day in April–June, every two or three days in winter. Never leave water standing in the saucer.\n\n## Soil and feeding\nA mix of garden soil, cow-dung manure and sand. Feed with liquid manure or mustard-cake water once a fortnight while it flowers.\n\n## Good to know\n- Pinch the tips after a flush of flowers for more branches.\n- Move to a pot one size bigger every year, before the rains.",
            "## কোথায় রাখবেন\nরোদ পড়া বারান্দা বা ছাদ: নিয়মিত ফুলের জন্য দিনে অন্তত পাঁচ ঘণ্টা রোদ।\n\n## জল\nমাটির ওপরের এক ইঞ্চি শুকোলে জল দিন; এপ্রিল–জুনে রোজ, শীতে দু-তিন দিন অন্তর। টবের নিচের থালায় জল জমতে দেবেন না।\n\n## মাটি ও সার\nবাগানের মাটি, গোবর সার আর বালি মিশিয়ে। ফুল ফোটার সময় পনেরো দিন অন্তর তরল সার বা সর্ষের খোলের জল দিন।\n\n## জেনে রাখুন\n- এক দফা ফুলের পর ডগা ছেঁটে দিলে ডালপালা বাড়ে।\n- বর্ষার আগে প্রতি বছর এক মাপ বড় টবে বদলান।",
        ],
        'seasonal' => [
            "## Where to keep it\nFull sun from November to February. These are winter flowers: they finish by March.\n\n## Watering\nKeep the soil just moist; water in the morning, never in the evening.\n\n## Soil and feeding\nLight, well-drained soil with manure. Feed every ten days once buds appear.\n\n## Good to know\n- Remove faded flowers to keep new ones coming.\n- Tall varieties need a thin stick to lean on.",
            "## কোথায় রাখবেন\nনভেম্বর থেকে ফেব্রুয়ারি পুরো রোদে। এগুলো শীতের ফুল: মার্চের মধ্যে শেষ।\n\n## জল\nমাটি হালকা ভেজা রাখুন; সকালে জল দিন, সন্ধ্যায় নয়।\n\n## মাটি ও সার\nঝুরঝুরে, জল বেরিয়ে যায় এমন মাটি, সঙ্গে সার। কুঁড়ি এলে দশ দিন অন্তর সার দিন।\n\n## জেনে রাখুন\n- শুকনো ফুল তুলে দিলে নতুন ফুল আসে।\n- লম্বা জাতের জন্য একটা সরু কাঠি লাগবে।",
        ],
        'medicinal' => [
            "## Where to keep it\nBright light with some direct sun; a window sill or balcony.\n\n## Watering\nWater when the top of the soil is dry. Less in winter.\n\n## Soil and feeding\nOrdinary garden soil with a little manure; feed once a month in summer.\n\n## Good to know\n- Pick leaves from the top so the plant grows bushy.\n- Ask at the counter how to use the leaves at home.",
            "## কোথায় রাখবেন\nআলো ভরা জায়গা, খানিক সরাসরি রোদ; জানলার ধারে বা বারান্দায়।\n\n## জল\nমাটির ওপরটা শুকোলে জল দিন। শীতে কম।\n\n## মাটি ও সার\nসাধারণ বাগানের মাটি, অল্প সার; গরমে মাসে একবার সার।\n\n## জেনে রাখুন\n- ওপরের পাতা তুললে গাছ ঝোপালো হয়।\n- বাড়িতে পাতা কীভাবে ব্যবহার করবেন, কাউন্টারে জিজ্ঞেস করুন।",
        ],
        'fruit' => [
            "## Where to keep it\nFull sun on a roof or in the ground. A grafted plant can fruit in a large pot.\n\n## Watering\nDeep watering twice a week; daily in peak summer. Keep it steady while it fruits.\n\n## Soil and feeding\nRich soil with manure and bone meal. Feed every month from February to October.\n\n## Good to know\n- Remove shoots that come from below the graft joint.\n- Move to a 16–18 inch pot or into the ground within a year.",
            "## কোথায় রাখবেন\nছাদে বা মাটিতে পুরো রোদে। কলমের গাছ বড় টবেও ফল দেয়।\n\n## জল\nসপ্তাহে দুবার ভালো করে জল; ভরা গরমে রোজ। ফল ধরার সময় নিয়মিত জল দিন।\n\n## মাটি ও সার\nসার আর হাড়গুঁড়ো মেশানো উর্বর মাটি। ফেব্রুয়ারি থেকে অক্টোবর মাসে একবার সার।\n\n## জেনে রাখুন\n- কলমের জোড়ের নিচ থেকে বেরোনো ডাল কেটে দিন।\n- এক বছরের মধ্যে ১৬–১৮ ইঞ্চি টবে বা মাটিতে বসান।",
        ],
        'indoor' => [
            "## Where to keep it\nIndoors in bright, indirect light. It also manages in a dim corner of a flat.\n\n## Watering\nOnce a week, less in winter. Too much water is the usual problem: let the soil dry between waterings.\n\n## Soil and feeding\nLoose potting mix; feed lightly every two months.\n\n## Good to know\n- Wipe dust off the leaves now and then.\n- Keep it away from the reach of pets.",
            "## কোথায় রাখবেন\nঘরের ভেতর আলো আছে কিন্তু সরাসরি রোদ নয় এমন জায়গায়। ফ্ল্যাটের কম আলোতেও চলে।\n\n## জল\nসপ্তাহে একবার, শীতে আরও কম। বেশি জলই সাধারণ সমস্যা: দুবার জলের মাঝে মাটি শুকোতে দিন।\n\n## মাটি ও সার\nঝুরঝুরে টবের মাটি; দু মাসে একবার হালকা সার।\n\n## জেনে রাখুন\n- মাঝে মাঝে পাতার ধুলো মুছে দিন।\n- পোষা প্রাণীর নাগালের বাইরে রাখুন।",
        ],
    ];

    public function run(): void
    {
        foreach (Plant::query()->whereNull('details')->get() as $plant) {
            [$en, $bn] = self::GUIDES[$plant->category] ?? self::GUIDES['flowering'];
            $plant->forceFill([
                'details' => $en,
                'details_bn' => $bn,
                'gallery' => $plant->gallery ?: ['nurseryTable', 'potted'],
            ])->saveQuietly();
        }
    }
}
