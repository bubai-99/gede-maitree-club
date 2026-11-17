<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

/**
 * Roles from the plan. Committee roles open the console; "member" is every family sign-in.
 */
enum Role: string
{
    use HasLabels;

    case Admin = 'admin';
    case Treasurer = 'treasurer';
    case PujaInCharge = 'puja';
    case CateringInCharge = 'catering';
    case NurseryInCharge = 'nursery';
    case Volunteer = 'volunteer';
    case WebsiteEditor = 'website_editor';
    case Member = 'member';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Admin => 'Admin (Secretary)',
            self::Treasurer => 'Treasurer',
            self::PujaInCharge => 'Puja in-charge',
            self::CateringInCharge => 'Catering in-charge',
            self::NurseryInCharge => 'Nursery in-charge',
            self::Volunteer => 'Volunteer',
            self::WebsiteEditor => 'Website editor',
            self::Member => 'Member',
        };
    }

    /**
     * Roles that open the committee console.
     *
     * @return list<self>
     */
    public static function committee(): array
    {
        return [self::Admin, self::Treasurer, self::PujaInCharge, self::CateringInCharge, self::NurseryInCharge, self::Volunteer, self::WebsiteEditor];
    }

    /**
     * What each role may do (spatie permissions). Admin passes every check (Gate::before).
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        $console = ['console.view', 'website.view'];

        return match ($this) {
            self::Admin => Permission::all(),
            self::Treasurer => [...$console, 'members.manage', 'fees.manage', 'books.view', 'books.manage', 'reports.view', 'chanda.manage'],
            self::PujaInCharge => [...$console, 'events.manage', 'chanda.record', 'chanda.manage', 'passes.manage', 'passes.scan'],
            self::CateringInCharge => [...$console, 'catering.manage', 'books.catering'],
            self::NurseryInCharge => [...$console, 'nursery.manage', 'books.nursery'],
            self::Volunteer => [...$console, 'passes.scan', 'chanda.record'],
            self::WebsiteEditor => [...$console, 'website.edit'],
            self::Member => ['member.app'],
        };
    }
}
