<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth;

use Blendhtml\Core\Auth\Entity\Role;
use Blendhtml\Core\Auth\Entity\User;
use Blendhtml\Doctrine\Doctrine;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use RuntimeException;
use Throwable;

final class AuthAdmin
{
    public static function setOtpLength(int $length): void
    {
        Configuration::set(
            AuthDefaults::OTP_LENGTH,
            Settings::validateOtpLength($length)
        );
    }

    public static function getOtpLength(): int
    {
        return Settings::otpLength();
    }

    public static function removeOtpLength(): void
    {
        Configuration::remove(AuthDefaults::OTP_LENGTH);
    }

    public static function setOtpLifetime(int $seconds): void
    {
        Configuration::set(
            AuthDefaults::OTP_LIFETIME,
            Settings::validateOtpLifetime($seconds)
        );
    }

    public static function getOtpLifetime(): int
    {
        return Settings::otpLifetime();
    }

    public static function removeOtpLifetime(): void
    {
        Configuration::remove(AuthDefaults::OTP_LIFETIME);
    }

    public static function setOtpMaxAttempts(int $attempts): void
    {
        Configuration::set(
            AuthDefaults::OTP_MAX_ATTEMPTS,
            Settings::validateOtpMaxAttempts($attempts)
        );
    }

    public static function getOtpMaxAttempts(): int
    {
        return Settings::otpMaxAttempts();
    }

    public static function removeOtpMaxAttempts(): void
    {
        Configuration::remove(
            AuthDefaults::OTP_MAX_ATTEMPTS
        );
    }

    public static function setSessionLifetime(int $seconds): void
    {
        Configuration::set(
            AuthDefaults::SESSION_LIFETIME,
            Settings::validateSessionLifetime($seconds)
        );
    }

    public static function getSessionLifetime(): int
    {
        return Settings::sessionLifetime();
    }

    public static function removeSessionLifetime(): void
    {
        Configuration::remove(
            AuthDefaults::SESSION_LIFETIME
        );
    }

    public static function setRequestCodeEmailRateLimit(
        int $maxAttempts,
        int $windowSeconds
    ): void {
        Configuration::set(
            AuthDefaults::REQUEST_CODE_EMAIL_RATE_LIMIT,
            Settings::validateRateLimit(
                $maxAttempts,
                $windowSeconds
            )
        );
    }

    public static function getRequestCodeEmailRateLimit(): array
    {
        return Settings::requestCodeEmailRateLimit();
    }

    public static function removeRequestCodeEmailRateLimit(): void
    {
        Configuration::remove(
            AuthDefaults::REQUEST_CODE_EMAIL_RATE_LIMIT
        );
    }

    public static function setRequestCodeIpRateLimit(
        int $maxAttempts,
        int $windowSeconds
    ): void {
        Configuration::set(
            AuthDefaults::REQUEST_CODE_IP_RATE_LIMIT,
            Settings::validateRateLimit(
                $maxAttempts,
                $windowSeconds
            )
        );
    }

    public static function getRequestCodeIpRateLimit(): array
    {
        return Settings::requestCodeIpRateLimit();
    }

    public static function removeRequestCodeIpRateLimit(): void
    {
        Configuration::remove(
            AuthDefaults::REQUEST_CODE_IP_RATE_LIMIT
        );
    }

    public static function setVerifyCodeEmailRateLimit(
        int $maxAttempts,
        int $windowSeconds
    ): void {
        Configuration::set(
            AuthDefaults::VERIFY_CODE_EMAIL_RATE_LIMIT,
            Settings::validateRateLimit(
                $maxAttempts,
                $windowSeconds
            )
        );
    }

    public static function getVerifyCodeEmailRateLimit(): array
    {
        return Settings::verifyCodeEmailRateLimit();
    }

    public static function removeVerifyCodeEmailRateLimit(): void
    {
        Configuration::remove(
            AuthDefaults::VERIFY_CODE_EMAIL_RATE_LIMIT
        );
    }

    public static function setVerifyCodeIpRateLimit(
        int $maxAttempts,
        int $windowSeconds
    ): void {
        Configuration::set(
            AuthDefaults::VERIFY_CODE_IP_RATE_LIMIT,
            Settings::validateRateLimit(
                $maxAttempts,
                $windowSeconds
            )
        );
    }

    public static function getVerifyCodeIpRateLimit(): array
    {
        return Settings::verifyCodeIpRateLimit();
    }

    public static function removeVerifyCodeIpRateLimit(): void
    {
        Configuration::remove(
            AuthDefaults::VERIFY_CODE_IP_RATE_LIMIT
        );
    }

    public static function setEmailAccessMode(
        string $mode
    ): void {
        $mode = strtolower(trim($mode));

        if (!in_array($mode, ['any', 'whitelist'], true)) {
            throw new \InvalidArgumentException(
                "Email access mode must be 'any' or 'whitelist'."
            );
        }

        Configuration::set(
            AuthDefaults::EMAIL_ACCESS_MODE,
            $mode
        );
    }

    public static function getEmailAccessMode(): string
    {
        return Settings::emailAccessMode();
    }

    public static function removeEmailAccessMode(): void
    {
        Configuration::remove(
            AuthDefaults::EMAIL_ACCESS_MODE
        );
    }

    public static function setCookieName(string $name): void
    {
        Configuration::set(
            AuthDefaults::COOKIE_NAME,
            Settings::validateCookieName($name)
        );
    }

    public static function getCookieName(): string
    {
        return Settings::cookieName();
    }

    public static function removeCookieName(): void
    {
        Configuration::remove(AuthDefaults::COOKIE_NAME);
    }

    public static function setCookieSameSite(
        string $sameSite
    ): void {
        Configuration::set(
            AuthDefaults::COOKIE_SAME_SITE,
            Settings::validateSameSite($sameSite)
        );
    }

    public static function getCookieSameSite(): string
    {
        return Settings::cookieSameSite();
    }

    public static function removeCookieSameSite(): void
    {
        Configuration::remove(
            AuthDefaults::COOKIE_SAME_SITE
        );
    }

    public static function setTrustedProxies(
        array $proxies
    ): void {
        Configuration::set(
            AuthDefaults::TRUSTED_PROXIES,
            ClientIp::normalizeTrustedProxies($proxies)
        );
    }

    public static function getTrustedProxies(): array
    {
        return Settings::trustedProxies();
    }

    public static function removeTrustedProxies(): void
    {
        Configuration::remove(
            AuthDefaults::TRUSTED_PROXIES
        );
    }

    public static function addEmailToWhitelist(
        string $email
    ): bool {
        $email = Email::normalize($email);
        $connection = Doctrine::em()
            ->getConnection();

        try {
            $connection->insert(
                'auth__email_whitelist',
                [
                    'email' => $email,
                    'created_at' =>
                        self::now()->format('Y-m-d H:i:s'),
                ]
            );

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }

    public static function removeEmailFromWhitelist(
        string $email
    ): bool {
        $email = Email::normalize($email);

        return Doctrine::em()
            ->getConnection()
            ->delete(
                'auth__email_whitelist',
                [
                    'email' => $email,
                ]
            ) > 0;
    }

    public static function isEmailWhitelisted(
        string $email
    ): bool {
        $email = Email::normalize($email);

        return Doctrine::em()
            ->getConnection()
            ->fetchOne(
                'SELECT 1 FROM auth__email_whitelist '
                . 'WHERE email = ?',
                [
                    $email,
                ]
            ) !== false;
    }

    public static function getEmailWhitelist(): array
    {
        return Doctrine::em()
            ->getConnection()
            ->fetchFirstColumn(
                'SELECT email FROM auth__email_whitelist '
                . 'ORDER BY email ASC'
            );
    }

    public static function createRole(string $name): Role
    {
        $name = RoleName::normalize($name);
        self::assertProjectRole($name);

        $entityManager = Doctrine::em();

        $existing = $entityManager
            ->getRepository(Role::class)
            ->findOneBy([
                'name' => $name,
            ]);

        if ($existing instanceof Role) {
            return $existing;
        }

        try {
            $entityManager
                ->getConnection()
                ->insert(
                    'auth__roles',
                    [
                        'name' => $name,
                        'created_at' =>
                            self::now()->format('Y-m-d H:i:s'),
                    ]
                );
        } catch (UniqueConstraintViolationException) {
            // A concurrent call created the same role.
        }

        $role = $entityManager
            ->getRepository(Role::class)
            ->findOneBy([
                'name' => $name,
            ]);

        if (!$role instanceof Role) {
            throw new RuntimeException(
                'Unable to create the authentication role.'
            );
        }

        return $role;
    }

    public static function deleteRole(string $name): bool
    {
        $name = RoleName::normalize($name, true);
        self::assertProjectRole($name);

        $entityManager = Doctrine::em();
        $connection = $entityManager->getConnection();

        $connection->beginTransaction();

        try {
            $role = $entityManager
                ->getRepository(Role::class)
                ->findOneBy([
                    'name' => $name,
                ]);

            if (!$role instanceof Role) {
                $connection->commit();

                return false;
            }

            $connection->executeStatement(
                'UPDATE auth__roles SET name = name WHERE id = ?',
                [
                    $role->getId(),
                ]
            );

            $entityManager->remove($role);
            $entityManager->flush();
            $connection->commit();

            return true;
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public static function assignRole(
        int $userId,
        string $role
    ): bool {
        $role = RoleName::normalize($role);

        return self::changeRole(
            $userId,
            $role,
            true
        );
    }

    public static function removeRole(
        int $userId,
        string $role
    ): bool {
        $role = RoleName::normalize($role, true);

        return self::changeRole(
            $userId,
            $role,
            false
        );
    }

    public static function syncRoles(
        int $userId,
        array $roles
    ): void {
        self::assertUserId($userId);

        $names = [];

        foreach ($roles as $role) {
            if (!is_string($role)) {
                throw new \InvalidArgumentException(
                    'Roles must be strings.'
                );
            }

            $name = RoleName::normalize($role);
            $names[$name] = true;
        }

        $names = array_keys($names);
        sort($names, SORT_STRING);

        $entityManager = Doctrine::em();
        $connection = $entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $user = $entityManager->find(
                User::class,
                $userId
            );

            if (!$user instanceof User) {
                throw new RuntimeException(
                    "Authentication user {$userId} does not exist."
                );
            }

            $connection->executeStatement(
                'UPDATE auth__users '
                . 'SET updated_at = updated_at WHERE id = ?',
                [
                    $userId,
                ]
            );

            $roleEntities = [];

            foreach ($names as $name) {
                $role = $entityManager
                    ->getRepository(Role::class)
                    ->findOneBy([
                        'name' => $name,
                    ]);

                if (!$role instanceof Role) {
                    throw new RuntimeException(
                        "Authentication role '{$name}' does not exist."
                    );
                }

                $connection->executeStatement(
                    'UPDATE auth__roles '
                    . 'SET name = name WHERE id = ?',
                    [
                        $role->getId(),
                    ]
                );

                $roleEntities[$name] = $role;
            }

            foreach (
                $user->getRoleEntities()->toArray()
                as $existingRole
            ) {
                if (
                    !isset(
                        $roleEntities[
                            $existingRole->getName()
                        ]
                    )
                ) {
                    $user->removeRole($existingRole);
                }
            }

            foreach ($roleEntities as $roleEntity) {
                $user->addRole($roleEntity);
            }

            $user->touch(self::now());
            $entityManager->flush();
            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public static function getRoles(): array
    {
        $roles = Doctrine::em()
            ->createQueryBuilder()
            ->select('role')
            ->from(Role::class, 'role')
            ->orderBy('role.name', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn(Role $role): string =>
                $role->getName(),
            $roles
        );
    }

    public static function getUserRoles(int $userId): array
    {
        self::assertUserId($userId);

        $user = Doctrine::em()
            ->find(User::class, $userId);

        if (!$user instanceof User) {
            throw new RuntimeException(
                "Authentication user {$userId} does not exist."
            );
        }

        return $user->roleNames();
    }

    public static function getConfiguration(): array
    {
        return [
            'otp' => [
                'length' => self::getOtpLength(),
                'lifetime_seconds' =>
                    self::getOtpLifetime(),
                'max_attempts' =>
                    self::getOtpMaxAttempts(),
            ],
            'session' => [
                'lifetime_seconds' =>
                    self::getSessionLifetime(),
            ],
            'email' => [
                'access_mode' =>
                    self::getEmailAccessMode(),
                'whitelist' =>
                    self::getEmailWhitelist(),
            ],
            'cookie' => [
                'name' => self::getCookieName(),
                'same_site' =>
                    self::getCookieSameSite(),
                'secure' => true,
                'http_only' => true,
                'path' => '/',
            ],
            'rate_limits' => [
                'request_code' => [
                    'email' =>
                        self::getRequestCodeEmailRateLimit(),
                    'ip' =>
                        self::getRequestCodeIpRateLimit(),
                ],
                'verify_code' => [
                    'email' =>
                        self::getVerifyCodeEmailRateLimit(),
                    'ip' =>
                        self::getVerifyCodeIpRateLimit(),
                ],
            ],
            'trusted_proxies' =>
                self::getTrustedProxies(),
        ];
    }

    private static function changeRole(
        int $userId,
        string $roleName,
        bool $assign
    ): bool {
        self::assertUserId($userId);

        $entityManager = Doctrine::em();
        $connection = $entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $user = $entityManager->find(
                User::class,
                $userId
            );

            if (!$user instanceof User) {
                throw new RuntimeException(
                    "Authentication user {$userId} does not exist."
                );
            }

            $connection->executeStatement(
                'UPDATE auth__users '
                . 'SET updated_at = updated_at WHERE id = ?',
                [
                    $userId,
                ]
            );

            $role = $entityManager
                ->getRepository(Role::class)
                ->findOneBy([
                    'name' => $roleName,
                ]);

            if (!$role instanceof Role) {
                if ($assign) {
                    throw new RuntimeException(
                        "Authentication role '{$roleName}' does not exist."
                    );
                }

                $connection->commit();

                return false;
            }

            $connection->executeStatement(
                'UPDATE auth__roles '
                . 'SET name = name WHERE id = ?',
                [
                    $role->getId(),
                ]
            );

            $changed = $assign
                ? $user->addRole($role)
                : $user->removeRole($role);

            if ($changed) {
                $user->touch(self::now());
                $entityManager->flush();
            }

            $connection->commit();

            return $changed;
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    private static function assertProjectRole(
        string $name
    ): void {
        if (BlendhtmlRoles::isProtected($name)) {
            throw new \InvalidArgumentException(
                "Blendhtml role '{$name}' is protected and cannot be managed as a project role."
            );
        }
    }

    private static function assertUserId(int $userId): void
    {
        if ($userId < 1) {
            throw new \InvalidArgumentException(
                'User ID must be a positive integer.'
            );
        }
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            'now',
            new DateTimeZone('UTC')
        );
    }
}
