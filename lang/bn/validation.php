<?php

use App\Support\FieldNames;

/*
| Bengali validation messages for every rule the forms use, and the field
| names (App\Support\FieldNames). Anything missing falls back to English.
*/

return [

    'accepted' => ':attribute-এ টিক দিন।',
    'after' => ':attribute :date-এর পরে হতে হবে।',
    'after_or_equal' => ':attribute :date বা তার পরে হতে হবে।',
    'alpha_dash' => ':attribute-এ শুধু অক্ষর, সংখ্যা, ড্যাশ ও আন্ডারস্কোর চলবে।',
    'array' => ':attribute ঠিক ধাঁচে নেই।',
    'before' => ':attribute :date-এর আগে হতে হবে।',
    'before_or_equal' => ':attribute :date বা তার আগে হতে হবে।',
    'between' => [
        'array' => ':attribute-এ :min থেকে :max-টি থাকতে হবে।',
        'file' => ':attribute :min থেকে :max কিলোবাইটের মধ্যে হতে হবে।',
        'numeric' => ':attribute :min থেকে :max-এর মধ্যে হতে হবে।',
        'string' => ':attribute :min থেকে :max অক্ষরের হতে হবে।',
    ],
    'boolean' => ':attribute হ্যাঁ বা না হতে হবে।',
    'confirmed' => 'দুবার দেওয়া :attribute মিলছে না।',
    'date' => ':attribute সঠিক তারিখ নয়।',
    'date_format' => ':attribute এই ধাঁচে লিখুন: :format।',
    'digits' => ':attribute :digits সংখ্যার হতে হবে।',
    'digits_between' => ':attribute :min থেকে :max সংখ্যার হতে হবে।',
    'distinct' => ':attribute দুবার দেওয়া হয়েছে।',
    'email' => 'সঠিক ইমেল ঠিকানা দিন, যেমন name@example.com।',
    'enum' => ':attribute-এর জন্য তালিকা থেকে একটি বাছুন।',
    'exists' => ':attribute-এর জন্য তালিকা থেকে একটি বাছুন।',
    'file' => ':attribute একটি ফাইল হতে হবে।',
    'image' => ':attribute একটি ছবি হতে হবে (JPG, PNG বা WebP)।',
    'in' => ':attribute-এর জন্য তালিকা থেকে একটি বাছুন।',
    'integer' => ':attribute পূর্ণ সংখ্যা হতে হবে।',
    'list' => ':attribute ঠিক ধাঁচে নেই।',
    'lte' => [
        'array' => ':attribute-এ :value-টির বেশি থাকতে পারবে না।',
        'file' => ':attribute :value কিলোবাইটের বেশি হতে পারবে না।',
        'numeric' => ':attribute :value-এর বেশি হতে পারবে না।',
        'string' => ':attribute :value অক্ষরের বেশি হতে পারবে না।',
    ],
    'max' => [
        'array' => ':attribute-এ :max-টির বেশি থাকতে পারবে না।',
        'file' => ':attribute :max কিলোবাইটের চেয়ে ছোট হতে হবে।',
        'numeric' => ':attribute :max-এর বেশি হতে পারবে না।',
        'string' => ':attribute :max অক্ষরের বেশি হতে পারবে না।',
    ],
    'mimes' => ':attribute এই ধরনের হতে হবে: :values।',
    'mimetypes' => ':attribute এই ধরনের হতে হবে: :values।',
    'min' => [
        'array' => ':attribute-এ অন্তত :min-টি লাগবে।',
        'file' => ':attribute অন্তত :min কিলোবাইট হতে হবে।',
        'numeric' => ':attribute অন্তত :min হতে হবে।',
        'string' => ':attribute অন্তত :min অক্ষরের হতে হবে।',
    ],
    'numeric' => ':attribute একটি সংখ্যা হতে হবে।',
    'prohibited' => 'কিছু একটা ভুল হয়েছে। আবার চেষ্টা করুন।',
    'regex' => ':attribute ঠিক ধাঁচে নেই।',
    'required' => ':attribute দিন।',
    'required_if' => ':other :value হলে :attribute দিন।',
    'required_with' => ':attribute-ও দিন।',
    'required_without' => ':attribute বা :values দিন।',
    'size' => [
        'array' => ':attribute-এ :size-টি থাকতে হবে।',
        'file' => ':attribute :size কিলোবাইট হতে হবে।',
        'numeric' => ':attribute :size হতে হবে।',
        'string' => ':attribute :size অক্ষরের হতে হবে।',
    ],
    'string' => ':attribute লেখা হতে হবে।',
    'unique' => 'এই :attribute আগে থেকেই ব্যবহার হচ্ছে।',
    'uploaded' => ':attribute আপলোড হয়নি। আরও ছোট ফাইল দিয়ে চেষ্টা করুন।',
    'url' => 'পুরো লিঙ্ক দিন, https:// দিয়ে শুরু।',
    'uuid' => 'কিছু একটা ভুল হয়েছে। আবার চেষ্টা করুন।',

    // Fields picked from a list say "বাছুন"
    'custom' => [
        ...FieldNames::choiceMessages(':attribute বাছুন।'),
        'upi_ref' => ['required_if' => 'ইউপিআই পেমেন্টের ইউটিআর নম্বর দিন (১২ সংখ্যার, পেমেন্ট অ্যাপে থাকে)।'],
        'utr' => ['required' => 'ইউপিআই পেমেন্টের ইউটিআর নম্বর দিন (১২ সংখ্যার, পেমেন্ট অ্যাপে থাকে)।'],
    ],

    // Field names (App\Support\FieldNames)
    'attributes' => FieldNames::bengali(),

    'values' => [
        'mode' => ['upi' => 'ইউপিআই', 'cash' => 'নগদ'],
        'kind' => ['wedding' => 'বিয়ের দিন'],
        'via' => ['email' => 'ইমেল', 'wa' => 'হোয়াটসঅ্যাপ'],
    ],
];
