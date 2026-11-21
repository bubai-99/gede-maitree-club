<?php

namespace App\Support;

/**
 * What every form field is called in error messages, in English and Bengali (lang/en and
 * lang/bn validation.php read these). A field ending in _bn is the Bengali copy of the field
 * before it ("title_bn" → "title (Bengali)"), so only the English field needs a name here.
 * Fields picked from a list (CHOICES) say "Choose …" when they are missing, not "Enter …".
 */
final class FieldNames
{
    /** @var array<string, array{0: string, 1: string}> field => [English, Bengali] */
    private const NAMES = [
        'about' => ['about', 'পরিচিতি'],
        'account' => ['cash or bank', 'নগদ না ব্যাংক'],
        'action' => ['button', 'বোতাম'],
        'active' => ['on the website', 'ওয়েবসাইটে'],
        'address' => ['address', 'ঠিকানা'],
        'agree' => ['the pledge', 'অঙ্গীকার'],
        'album' => ['album', 'অ্যালবাম'],
        'amount' => ['amount', 'টাকার অঙ্ক'],
        'area' => ['lane or area', 'গলি বা এলাকা'],
        'area_id' => ['area', 'এলাকা'],
        'audience' => ['who sees it', 'কে দেখবে'],
        'bill' => ['bill photo', 'বিলের ছবি'],
        'blood_group' => ['blood group', 'রক্তের গ্রুপ'],
        'body' => ['text', 'লেখা'],
        'book' => ['book', 'খাতা'],
        'bring' => ['what to bring', 'কী আনবেন'],
        'budget' => ['budget', 'বাজেট'],
        'buyer_name' => ['buyer', 'ক্রেতা'],
        'capacity' => ['seats', 'আসন'],
        'category' => ['category', 'বিভাগ'],
        'category_id' => ['category', 'বিভাগ'],
        'chanda_target' => ['chanda target', 'চাঁদার লক্ষ্য'],
        'code' => ['code', 'কোড'],
        'collected_by' => ['collected by', 'কে তুলেছেন'],
        'collector_id' => ['collector', 'চাঁদা তোলেন যিনি'],
        'contact' => ['your mobile number or email', 'আপনার মোবাইল নম্বর বা ইমেল'],
        'contact.address' => ['address', 'ঠিকানা'],
        'contact.email' => ['email', 'ইমেল'],
        'contact.facebook' => ['Facebook link', 'ফেসবুক লিঙ্ক'],
        'contact.hours' => ['opening hours', 'খোলার সময়'],
        'contact.instagram' => ['Instagram link', 'ইনস্টাগ্রাম লিঙ্ক'],
        'contact.member_discount_percent' => ['members’ discount', 'সদস্যদের ছাড়'],
        'contact.phone' => ['phone number', 'ফোন নম্বর'],
        'contact.upi' => ['UPI ID', 'ইউপিআই আইডি'],
        'contact.whatsapp' => ['WhatsApp number', 'হোয়াটসঅ্যাপ নম্বর'],
        'contact.youtube' => ['YouTube link', 'ইউটিউব লিঙ্ক'],
        'contact_role' => ['contact person', 'যোগাযোগের মানুষ'],
        'cost' => ['cost per plant', 'গাছ প্রতি দাম'],
        'counted' => ['cash counted', 'গোনা টাকা'],
        'customer_name' => ['customer', 'গ্রাহক'],
        'date' => ['date', 'তারিখ'],
        'deliverable' => ['what they get', 'বিনিময়ে কী পাবেন'],
        'dob' => ['date of birth', 'জন্মতারিখ'],
        'donor_name' => ['donor', 'দাতা'],
        'donor_phone' => ['donor’s mobile number', 'দাতার মোবাইল নম্বর'],
        'email' => ['email', 'ইমেল'],
        'entry_date' => ['date', 'তারিখ'],
        'event' => ['event', 'অনুষ্ঠান'],
        'event_date' => ['date of the event', 'অনুষ্ঠানের তারিখ'],
        'event_id' => ['event', 'অনুষ্ঠান'],
        'family' => ['family', 'পরিবার'],
        'family_id' => ['family', 'পরিবার'],
        'family_limit' => ['people per family', 'পরিবার প্রতি জন'],
        'family_no' => ['family number', 'পরিবার নম্বর'],
        'for' => ['who it is for', 'কাদের জন্য'],
        'from' => ['where they came from', 'কোথা থেকে'],
        'gate' => ['gate', 'গেট'],
        'guests' => ['number of guests', 'অতিথির সংখ্যা'],
        'head' => ['head', 'খাত'],
        'houses' => ['number of houses', 'বাড়ির সংখ্যা'],
        'images_upload' => ['photos', 'ছবি'],
        'images_upload.*' => ['each photo', 'প্রতিটি ছবি'],
        'kind' => ['kind', 'ধরন'],
        'lagna' => ['lagna', 'লগ্ন'],
        'lines' => ['plants on the bill', 'বিলের গাছ'],
        'lines.*' => ['quantity', 'সংখ্যা'],
        'link' => ['link', 'লিঙ্ক'],
        'login' => ['your mobile number or email', 'আপনার মোবাইল নম্বর বা ইমেল'],
        'member_price' => ['member price', 'সদস্য-দাম'],
        'members' => ['family members', 'পরিবারের সদস্য'],
        'members.*.dob' => ['date of birth', 'জন্মতারিখ'],
        'members.*.name' => ['name of the family member', 'পরিবারের সদস্যের নাম'],
        'members.*.relation' => ['relation', 'সম্পর্ক'],
        'menu' => ['menu', 'মেনু'],
        'menu.*.label' => ['menu label', 'মেনুর নাম'],
        'message' => ['your message', 'আপনার বার্তা'],
        'min_guests' => ['minimum guests', 'সর্বনিম্ন অতিথি'],
        'mode' => ['how it was paid', 'কীভাবে দেওয়া'],
        'month' => ['month', 'মাস'],
        'months' => ['number of months', 'মাসের সংখ্যা'],
        'name' => ['your name', 'আপনার নাম'],
        'note' => ['note', 'নোট'],
        'notes' => ['notes', 'নোট'],
        'occasion' => ['occasion', 'অনুষ্ঠান'],
        'package' => ['package', 'প্যাকেজ'],
        'package_id' => ['package', 'প্যাকেজ'],
        'password' => ['password', 'পাসওয়ার্ড'],
        'pay_by' => ['how you will pay', 'কীভাবে দেবেন'],
        'people' => ['number of people', 'লোকের সংখ্যা'],
        'phone' => ['mobile number', 'মোবাইল নম্বর'],
        'photo' => ['photo', 'ছবি'],
        'photo_upload' => ['photo', 'ছবি'],
        'place' => ['place', 'জায়গা'],
        'plan' => ['plan', 'প্ল্যান'],
        'pot' => ['pot size', 'টবের মাপ'],
        'price' => ['price', 'দাম'],
        'qty' => ['quantity', 'সংখ্যা'],
        'rate' => ['rate', 'দর'],
        'reason' => ['reason', 'কারণ'],
        'received_on' => ['date received', 'পাওয়ার তারিখ'],
        'reference' => ['reference', 'রেফারেন্স'],
        'refund' => ['refund', 'ফেরত'],
        'registration_closes' => ['last day to register', 'নাম লেখানোর শেষ দিন'],
        'reply_by' => ['how we should reply', 'কীভাবে উত্তর দেব'],
        'role' => ['role', 'দায়িত্ব'],
        'schedule' => ['programme', 'অনুষ্ঠানসূচি'],
        'server_name' => ['name', 'নাম'],
        'since' => ['member since', 'কবে থেকে সদস্য'],
        'skills' => ['how you can help', 'কীভাবে সাহায্য করবেন'],
        'slug' => ['link name', 'লিঙ্কের নাম'],
        'sort' => ['order', 'ক্রম'],
        'source' => ['panjika followed', 'যে পঞ্জিকা মানা হয়'],
        'statement' => ['bank statement', 'ব্যাংক স্টেটমেন্ট'],
        'status' => ['status', 'অবস্থা'],
        'stock' => ['stock', 'স্টক'],
        'summary' => ['summary', 'সারাংশ'],
        'tag' => ['tag', 'ট্যাগ'],
        'target' => ['target', 'লক্ষ্য'],
        'time' => ['time', 'সময়'],
        'time_label' => ['time shown', 'দেখানো সময়'],
        'tip' => ['care tip', 'যত্নের টিপ'],
        'tithi' => ['tithi', 'তিথি'],
        'title' => ['title', 'শিরোনাম'],
        'topic' => ['topic', 'বিষয়'],
        'upi_ref' => ['UTR number', 'ইউটিআর নম্বর'],
        'utr' => ['UTR number', 'ইউটিআর নম্বর'],
        'valid_until' => ['valid until', 'বৈধ'],
        'venue' => ['venue', 'অনুষ্ঠানের জায়গা'],
        'via' => ['where to send it', 'কোথায় পাঠাব'],
        'videos' => ['videos', 'ভিডিও'],
        'visited' => ['houses visited', 'যত বাড়িতে যাওয়া হয়েছে'],
        'wage' => ['wage', 'মজুরি'],
        'water' => ['water', 'জল'],
        'year' => ['year', 'বছর'],
    ];

    /** Fields picked from a list, a switch or a set of options. */
    private const CHOICES = [
        'account', 'area_id', 'audience', 'blood_group', 'book', 'category', 'category_id', 'collected_by', 'collector_id',
        'contact_role', 'event', 'event_id', 'family', 'family_id', 'head', 'kind', 'mode', 'occasion', 'package',
        'package_id', 'pay_by', 'plan', 'reply_by', 'role', 'status', 'tithi', 'topic', 'via', 'agree',
    ];

    /**
     * @return array<string, string>
     */
    public static function english(): array
    {
        return self::names(0, ' (Bengali)');
    }

    /**
     * @return array<string, string>
     */
    public static function bengali(): array
    {
        return self::names(1, ' (বাংলায়)');
    }

    /**
     * "Choose …" instead of "Enter …" for fields picked from a list.
     *
     * @return array<string, array<string, string>>
     */
    public static function choiceMessages(string $message): array
    {
        return array_fill_keys(self::CHOICES, ['required' => $message, 'required_if' => $message]);
    }

    /**
     * @return array<string, string>
     */
    private static function names(int $i, string $bengaliSuffix): array
    {
        $out = [];
        foreach (self::NAMES as $field => $names) {
            $out[$field] = $names[$i];
            if (! str_contains($field, '.')) {
                $out[$field.'_bn'] ??= $names[$i].$bengaliSuffix;
            }
        }

        return $out;
    }
}
