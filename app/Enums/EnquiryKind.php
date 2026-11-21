<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum EnquiryKind: string
{
    use HasLabels;

    case Contact = 'contact';
    case Join = 'join';
    case Catering = 'catering';
    case Volunteer = 'volunteer';
    case Photos = 'photos';
    case Chanda = 'chanda';
    case News = 'news';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Contact => 'Contact',
            self::Join => 'Membership application',
            self::Catering => 'Catering enquiry',
            self::Volunteer => 'Volunteer',
            self::Photos => 'Photos',
            self::Chanda => 'Chanda pledge',
            self::News => 'Club letter sign-up',
        };
    }
}
