<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth;

final class RoleName
{
    public static function normalize(
        string $role,
        bool $allowIdentityName = false
    ): string {
        $role = strtolower(trim($role));

        if (
            $role === ''
            || strlen($role) > 64
            || !preg_match(
                '/^[a-z][a-z0-9_-]*$/',
                $role
            )
        ) {
            throw new \InvalidArgumentException(
                'Role names must begin with a letter and contain only lowercase letters, numbers, underscores, or hyphens.'
            );
        }

        if ($role === 'user' && !$allowIdentityName) {
            throw new \InvalidArgumentException(
                "The identity name 'user' is not an application role."
            );
        }

        return $role;
    }
}
