<?php

/*
| Website text the committee can change in the console (Website → Pages).
| Each default is an English translation key with its Bengali in lang/bn.json;
| saved text (pages.blocks) wins over the default, in each language.
*/

return [
    'home' => [
        'label' => 'Home page',
        'photo' => 'pujaNight',
        'fields' => [
            'hero_kicker' => ['label' => 'Hero: Small heading', 'default' => 'Gede Uttarpara · Est. 2014'],
            'hero_title' => ['label' => 'Hero: Title', 'default' => 'Twelve years of pujas, play and looking out for each other'],
            'hero_sub' => ['label' => 'Hero: Text under the title', 'default' => 'A club run by neighbours. Durga, Kali and Saraswati Puja, sports day, blood donation, a serving team for the para’s weddings and a nursery by the gate.', 'long' => true],
            'story_title' => ['label' => 'Story: Title', 'default' => 'From one rented room to the heart of the para'],
            'story_text' => ['label' => 'Story: Text', 'default' => 'In 2014 a few friends from the para started the club in a rented room. Today it runs the para’s pujas, serves at its weddings, sells plants at the gate, and shows every rupee to its members.', 'long' => true],
        ],
    ],
    'events' => [
        'label' => 'Events',
        'photo' => 'pujaCrowd',
        'fields' => [
            'kicker' => ['label' => 'Small heading', 'default' => 'What’s on'],
            'title' => ['label' => 'Title', 'default' => 'Events'],
            'intro' => ['label' => 'Introduction', 'default' => 'Pujas, sports, service days and members’ outings. Everyone is welcome; where a pass is needed, members get it from the app.', 'long' => true],
        ],
    ],
    'catering' => [
        'label' => 'Serving',
        'photo' => 'catBuffet',
        'fields' => [
            'kicker' => ['label' => 'Small heading', 'default' => 'Catering · serving only'],
            'title' => ['label' => 'Title', 'default' => 'Maitree Serving'],
            'intro' => ['label' => 'Introduction', 'default' => 'The team that serves bhog to thousands at the pujas, serving at your occasion. We don’t cook: the food comes from your cook or caterer, and we serve it. The profit goes back into the club.', 'long' => true],
            'note' => ['label' => 'Note', 'default' => 'Members pay :percent% less. We don’t cook or supply the food.'],
            'quote' => ['label' => 'After an enquiry', 'default' => 'Debashis Pal will call within a day to confirm the guests, timing and price. A 20% advance books the date.', 'long' => true],
            'review_tags' => ['label' => 'Reviews: What people mention (a comma between each)', 'default' => 'On time, Polite team, Clean tables, Clear bill, Looked after elders'],
        ],
    ],
    'nursery' => [
        'label' => 'Nursery',
        'photo' => 'nurseryTable',
        'fields' => [
            'kicker' => ['label' => 'Small heading', 'default' => 'Plant nursery'],
            'title' => ['label' => 'Title', 'default' => 'Maitree Nursery'],
            'intro' => ['label' => 'Introduction', 'default' => 'Flowers, fruit saplings and house plants beside the club gate. Pick them here, send the list on WhatsApp, and collect from the gate.', 'long' => true],
            'hours' => ['label' => 'Banner: Nursery hours', 'default' => '7–10 am & 5–8 pm'],
            'pickup' => ['label' => 'Banner: Where to collect', 'default' => 'Collect at the club gate'],
            'step1_title' => ['label' => 'How to buy, step 1: Title', 'default' => 'Pick plants'],
            'step1_text' => ['label' => 'How to buy, step 1: Text', 'default' => 'Add them below; the total updates as you go.'],
            'step2_title' => ['label' => 'How to buy, step 2: Title', 'default' => 'Send on WhatsApp'],
            'step2_text' => ['label' => 'How to buy, step 2: Text', 'default' => 'Sutapa-di sets them aside and replies.'],
            'step3_title' => ['label' => 'How to buy, step 3: Title', 'default' => 'Collect & pay'],
            'step3_text' => ['label' => 'How to buy, step 3: Text', 'default' => 'Cash or UPI at the gate, with a receipt.'],
            'card1_title' => ['label' => 'Card 1: Title', 'default' => 'This winter'],
            'card1_text' => ['label' => 'Card 1: Text', 'default' => 'Dahlia, chrysanthemum and marigold seedlings until February.'],
            'card2_title' => ['label' => 'Card 2: Title', 'default' => 'Care advice, free'],
            'card2_text' => ['label' => 'Card 2: Text', 'default' => 'Where to keep it, how much water: just ask at the gate.'],
            'card3_title' => ['label' => 'Card 3: Title', 'default' => 'Gifts & pujas'],
            'card3_text' => ['label' => 'Card 3: Text', 'default' => 'Buying tulsi or fruit saplings in bulk for a griha pravesh or puja? Ask for a price.', 'long' => true],
            'review_tags' => ['label' => 'Reviews: What people mention (a comma between each)', 'default' => 'Healthy plants, Care advice, Fair prices, Ready next morning'],
        ],
    ],
    'committee' => [
        'label' => 'About page',
        'photo' => 'pujaMorning',
        'fields' => [
            'kicker' => ['label' => 'Small heading', 'default' => 'About us'],
            'title' => ['label' => 'Title', 'default' => 'A club run by its neighbours'],
            'intro' => ['label' => 'Introduction', 'default' => 'A registered society, Reg. No. S-0027153 / 2022-23. The committee is elected by members at the AGM every two years, and everyone on it is a volunteer.', 'long' => true],
            'mission' => ['label' => 'Mission', 'default' => 'To keep Gede Uttarpara close: celebrate together, look after each other, and run it all in the open.', 'long' => true],
            'value1_title' => ['label' => 'Value 1: Title', 'default' => 'Open to everyone'],
            'value1_text' => ['label' => 'Value 1: Text', 'default' => 'Any family on the para can join, and everyone is welcome at the pujas.'],
            'value2_title' => ['label' => 'Value 2: Title', 'default' => 'Open books'],
            'value2_text' => ['label' => 'Value 2: Text', 'default' => 'Every rupee goes in the khata with its bill, and the accounts are audited each year.', 'long' => true],
            'value3_title' => ['label' => 'Value 3: Title', 'default' => 'Everyone has a say'],
            'value3_text' => ['label' => 'Value 3: Text', 'default' => 'The committee is elected by members, and big decisions go to the AGM.'],
            'value4_title' => ['label' => 'Value 4: Title', 'default' => 'There when it’s needed'],
            'value4_text' => ['label' => 'Value 4: Text', 'default' => 'From blood camps to a hand on a hard day, the club shows up for the para.', 'long' => true],
            'clubroom_text' => ['label' => 'Clubroom: Text', 'default' => '14/2 Netaji Subhas Road, Gede Uttarpara, Kolkata 700 084 · daily 6–9 pm'],
            'group_kicker' => ['label' => 'Group: Small heading', 'default' => 'The people behind it'],
            'group_title' => ['label' => 'Group: Title', 'default' => 'Friends first, then a club'],
            'group_caption' => ['label' => 'Group: Caption', 'default' => 'Every puja, match and camp is run by people like these: neighbours who turn up, year after year.', 'long' => true],
        ],
    ],
    'gallery' => [
        'label' => 'Gallery',
        'photo' => 'sindoor',
        'fields' => [
            'kicker' => ['label' => 'Small heading', 'default' => 'The para in pictures'],
            'title' => ['label' => 'Title', 'default' => 'Gallery'],
            'intro' => ['label' => 'Introduction', 'default' => 'Every puja, match and camp, kept by date. Open an album to see all its photos and videos.', 'long' => true],
        ],
    ],
    'volunteer' => [
        'label' => 'Get involved',
        'photo' => 'blood',
        'fields' => [
            'kicker' => ['label' => 'Small heading', 'default' => 'Get involved'],
            'title' => ['label' => 'Title', 'default' => 'Every event runs on neighbours'],
            'intro' => ['label' => 'Introduction', 'default' => 'Pandal, bhog, the gate, the sports ground. Even one evening helps, and you don’t have to be a member.', 'long' => true],
        ],
    ],
    'join' => [
        'label' => 'Become a member',
        'photo' => 'runners',
        'fields' => [
            'kicker' => ['label' => 'Small heading', 'default' => 'Membership'],
            'title' => ['label' => 'Title', 'default' => 'Become a member'],
            'intro' => ['label' => 'Introduction', 'default' => 'A three-step form that takes five minutes. People in the same household can join on the same form.', 'long' => true],
        ],
    ],
    'notices' => [
        'label' => 'Notices',
        'photo' => 'garlands',
        'fields' => [
            'kicker' => ['label' => 'Small heading', 'default' => 'Club news'],
            'title' => ['label' => 'Title', 'default' => 'Notice board'],
            'intro' => ['label' => 'Introduction', 'default' => 'Meetings, events, accounts, lost and found. The same notices that go up on the board at the club gate.', 'long' => true],
        ],
    ],
    'news' => [
        'label' => 'News',
        'photo' => 'pujaCrowd',
        'fields' => [
            'kicker' => ['label' => 'Small heading', 'default' => 'From the para'],
            'title' => ['label' => 'Title', 'default' => 'News & stories'],
            'intro' => ['label' => 'Introduction', 'default' => 'What happened at the pujas, on the field and at the service camps, with photos and videos.', 'long' => true],
        ],
    ],
    'chanda' => [
        'label' => 'Chanda & accounts',
        'photo' => 'diyaTray',
        'fields' => [
            'kicker' => ['label' => 'Small heading', 'default' => 'Puja chanda · open books'],
            'title' => ['label' => 'Title', 'default' => 'Chanda & accounts'],
            'intro' => ['label' => 'Introduction', 'default' => 'The club runs on puja chanda and members’ subscriptions. Here is what we are raising now, where last year’s money went, and the papers behind it.', 'long' => true],
        ],
    ],
    'contact' => [
        'label' => 'Contact',
        'photo' => 'diyas',
        'fields' => [
            'kicker' => ['label' => 'Small heading', 'default' => 'Contact & directions'],
            'title' => ['label' => 'Title', 'default' => 'Come to the club'],
            'intro' => ['label' => 'Introduction', 'default' => 'Drop into the club room any evening, call, or write below. We pass it to the right person.', 'long' => true],
        ],
    ],
];
