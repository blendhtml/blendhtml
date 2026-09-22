<?php

namespace Blendhtml\Core\Auth;

final class AuthContext
{
    // @todo Might be obsolete to expose here
    public function check(): bool
    {
        return Auth::check();
    }

    public function user(): mixed
    {
        return Auth::user();
    }

    public function id(): ?int
    {
        return Auth::id();
    }

    public function roles(): array
    {
        return Auth::roles();
    }

    public function hasRole(string $role): bool
    {
        return Auth::hasRole($role);
    }

    public function hasAnyRole(array $roles): bool
    {
        return Auth::hasAnyRole($roles);
    }

    public function hasAllRoles(array $roles): bool
    {
        return Auth::hasAllRoles($roles);
    }
}