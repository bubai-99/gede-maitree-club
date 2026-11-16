<?php

namespace App\Support;

/**
 * Choices offered by the website forms. Labels are English translation keys.
 */
final class FormOptions
{
    /** Contact form topics and who answers them. */
    public const CONTACT_TOPICS = [
        'membership' => ['label' => 'Membership', 'role' => 'Secretary'],
        'serving' => ['label' => 'Serving team booking', 'role' => 'Catering in-charge'],
        'nursery' => ['label' => 'Nursery and plants', 'role' => 'Nursery in-charge'],
        'chanda' => ['label' => 'Chanda, sponsorship or donation', 'role' => 'Treasurer'],
        'events' => ['label' => 'Events and volunteering', 'role' => 'Puja in-charge'],
        'photo' => ['label' => 'Remove a photo of me', 'role' => 'Secretary'],
        'other' => ['label' => 'Something else', 'role' => 'Secretary'],
    ];

    public const AREAS = [
        'Lane 1' => ['label' => 'Lane 1'],
        'Lane 2' => ['label' => 'Lane 2'],
        'Lane 3' => ['label' => 'Lane 3'],
        'Pukur Par' => ['label' => 'Pukur Par'],
        'School Road' => ['label' => 'School Road'],
        'Elsewhere' => ['label' => 'Somewhere else'],
    ];

    public const VOLUNTEER_SKILLS = [
        'gate' => ['label' => 'Gate & scanner'],
        'chanda' => ['label' => 'Chanda'],
        'registration' => ['label' => 'Registration desk'],
        'first-aid' => ['label' => 'First aid'],
        'sports' => ['label' => 'Sports'],
        'bhog' => ['label' => 'Bhog & kitchen'],
        'serving' => ['label' => 'Serving & set-up'],
        'sound' => ['label' => 'Sound & lights'],
    ];

    public const VOLUNTEER_TIMES = [
        'weekends' => ['label' => 'Weekends'],
        'evenings' => ['label' => 'Evenings'],
        'pujas' => ['label' => 'Only during pujas'],
    ];

    public const OCCASIONS = [
        'annaprashan' => ['label' => 'Annaprashan'],
        'wedding' => ['label' => 'Wedding'],
        'reception' => ['label' => 'Reception'],
        'birthday' => ['label' => 'Birthday'],
        'shraddha' => ['label' => 'Shraddha'],
        'other' => ['label' => 'Office / other'],
    ];

    public const REPLY_BY = [
        'whatsapp' => ['label' => 'WhatsApp'],
        'call' => ['label' => 'Phone call'],
    ];

    public const PLANS = [
        'monthly' => ['label' => '₹100 / month'],
        'yearly' => ['label' => '₹1,100 / year'],
    ];

    public const PAY_BY = [
        'upi' => ['label' => 'UPI'],
        'cash' => ['label' => 'At the club room'],
        'visit' => ['label' => 'Visit my home'],
    ];

    /**
     * @param  array<string, array{label: string}>  $options
     * @return list<string>
     */
    public static function keys(array $options): array
    {
        return array_map('strval', array_keys($options));
    }
}
