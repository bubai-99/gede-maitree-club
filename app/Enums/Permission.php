<?php

namespace App\Enums;

/**
 * Every permission name used with Gate / @can. Seeded by RoleSeeder.
 */
final class Permission
{
    public const LIST = [
        'console.view',
        'website.view',
        'website.edit',
        'activity.view',
        'roles.manage',
        'members.manage',
        'fees.manage',
        'books.view',
        'books.manage',
        'books.catering',
        'books.nursery',
        'reports.view',
        'events.manage',
        'chanda.record',
        'chanda.manage',
        'passes.manage',
        'passes.scan',
        'catering.manage',
        'nursery.manage',
        'member.app',
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return self::LIST;
    }
}
