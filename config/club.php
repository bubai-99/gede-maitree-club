<?php

/*
| Club details used when the committee has not yet saved its own in the
| console (site_settings). SAMPLE values from the prototype: the address,
| phone numbers and UPI ID are placeholders until the club confirms them.
| The registration number and founding year are real.
*/

return [
    'name' => 'Gede Maitree Club',
    'name_bn' => 'গেদে মৈত্রী ক্লাব',
    'para' => 'Netaji Para',
    'para_bn' => 'নেতাজি পাড়া',
    'estd' => 2014,
    'reg_no' => 'S-0027153 / 2022-23',
    'address' => '14/2 Netaji Subhas Road, Netaji Para, Kolkata 700 084',
    'address_bn' => '১৪/২ নেতাজি সুভাষ রোড, নেতাজি পাড়া, কলকাতা ৭০০ ০৮৪',
    'street' => '14/2 Netaji Subhas Road, Netaji Para',
    'locality' => 'Kolkata',
    'postal_code' => '700084',
    'phone' => '+91 98300 12345',
    'whatsapp' => '919830012345',
    'email' => 'hello@example.org',
    'upi' => 'maitriclub@sbi',
    'hours' => 'Open daily, 6 – 9 pm',
    'hours_bn' => 'প্রতিদিন সন্ধ্যা ৬টা – ৯টা',
    'opening_hours' => 'Mo-Su 18:00-21:00',
    'monthly_fee' => 100,
    'admission_fee' => 500,
    'member_discount_percent' => 10,

    // Catering: advance asked for with the quote, and the wage of one server for one booking. SAMPLE.
    'catering_advance_percent' => 20,
    'catering_server_wage' => 500,

    // SAMPLE numbers shown on the home and About pages until the committee sets them.
    'stats' => [
        'volunteers' => 61,
        'bhog_plates' => 4200,
        'year' => '2025–26',
    ],
    'social' => [
        'facebook' => 'https://www.facebook.com/gedemaitreeclub',
        'instagram' => 'https://www.instagram.com/gedemaitreeclub',
        'youtube' => 'https://www.youtube.com/@gedemaitreeclub',
    ],

    // Languages the site can be shown in.
    'locales' => ['en', 'bn'],
];
