<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth;

final class BlendhtmlRoles
{
    public const ADMIN = 'admin';
    public const VISIT_METRICS = 'visit_metrics';
    public const CONTENT_EDITING = 'content_editing';
    public const AB_TESTING = 'ab_testing';

    private const ROLES = [
        self::ADMIN,
        self::VISIT_METRICS,
        self::CONTENT_EDITING,
        self::AB_TESTING,
    ];

    public static function all(): array
    {
        return self::ROLES;
    }

    public static function isProtected(string $role): bool
    {
        return in_array(
            $role,
            self::ROLES,
            true
        );
    }
}
