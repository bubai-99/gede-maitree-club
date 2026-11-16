<?php

/*
| Bengali validation messages for the rules the app uses. Any rule not listed
| here falls back to the English message in lang/en/validation.php.
*/

return [

    'accepted' => ':attribute মেনে নিতে হবে।',
    'after' => ':attribute অবশ্যই :date-এর পরের তারিখ হবে।',
    'after_or_equal' => ':attribute অবশ্যই :date বা তার পরের তারিখ হবে।',
    'array' => ':attribute একটি তালিকা হতে হবে।',
    'before' => ':attribute অবশ্যই :date-এর আগের তারিখ হবে।',
    'boolean' => ':attribute হ্যাঁ বা না হতে হবে।',
    'confirmed' => ':attribute দুবার একই দিতে হবে।',
    'date' => ':attribute একটি সঠিক তারিখ নয়।',
    'digits' => ':attribute :digits সংখ্যার হতে হবে।',
    'digits_between' => ':attribute :min থেকে :max সংখ্যার মধ্যে হতে হবে।',
    'email' => ':attribute একটি সঠিক ইমেল ঠিকানা হতে হবে।',
    'exists' => 'বেছে নেওয়া :attribute সঠিক নয়।',
    'file' => ':attribute একটি ফাইল হতে হবে।',
    'image' => ':attribute একটি ছবি হতে হবে।',
    'in' => 'বেছে নেওয়া :attribute সঠিক নয়।',
    'integer' => ':attribute একটি পূর্ণ সংখ্যা হতে হবে।',
    'list' => ':attribute একটি তালিকা হতে হবে।',
    'max' => [
        'array' => ':attribute-এ :max-টির বেশি থাকতে পারবে না।',
        'file' => ':attribute :max কিলোবাইটের বেশি হতে পারবে না।',
        'numeric' => ':attribute :max-এর বেশি হতে পারবে না।',
        'string' => ':attribute :max অক্ষরের বেশি হতে পারবে না।',
    ],
    'mimes' => ':attribute এই ধরনের ফাইল হতে হবে: :values।',
    'mimetypes' => ':attribute এই ধরনের ফাইল হতে হবে: :values।',
    'min' => [
        'array' => ':attribute-এ অন্তত :min-টি থাকতে হবে।',
        'file' => ':attribute অন্তত :min কিলোবাইট হতে হবে।',
        'numeric' => ':attribute অন্তত :min হতে হবে।',
        'string' => ':attribute অন্তত :min অক্ষরের হতে হবে।',
    ],
    'numeric' => ':attribute একটি সংখ্যা হতে হবে।',
    'prohibited' => ':attribute ঘরটি ফাঁকা রাখুন।',
    'regex' => ':attribute সঠিক ধাঁচে নেই।',
    'required' => ':attribute দিতে হবে।',
    'required_if' => ':other :value হলে :attribute দিতে হবে।',
    'required_with' => ':values দিলে :attribute-ও দিতে হবে।',
    'size' => [
        'string' => ':attribute :size অক্ষরের হতে হবে।',
    ],
    'string' => ':attribute লেখা হতে হবে।',
    'unique' => 'এই :attribute আগে থেকেই আছে।',
    'uploaded' => ':attribute আপলোড হয়নি।',
    'url' => ':attribute একটি সঠিক লিঙ্ক হতে হবে।',

    'custom' => [],

    'attributes' => [
        'name' => 'নাম',
        'phone' => 'মোবাইল নম্বর',
        'email' => 'ইমেল',
        'login' => 'মোবাইল নম্বর বা ইমেল',
        'password' => 'পাসওয়ার্ড',
        'code' => 'কোড',
        'message' => 'বার্তা',
        'address' => 'ঠিকানা',
        'area' => 'গলি / এলাকা',
        'dob' => 'জন্মতারিখ',
        'blood_group' => 'রক্তের গ্রুপ',
        'guests' => 'অতিথির সংখ্যা',
        'event_date' => 'অনুষ্ঠানের তারিখ',
        'package' => 'প্যাকেজ',
        'venue' => 'জায়গা',
        'title' => 'শিরোনাম',
        'slug' => 'লিঙ্কের নাম',
        'photo' => 'ছবি',
        'members' => 'সদস্য',
        'agree' => 'অঙ্গীকার',
        'skills' => 'কাজ',
        'topic' => 'বিষয়',
    ],

];
