<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth;

use Blendhtml\Core\Adapter;
use Blendhtml\Core\Auth\Entity\LoginCode;
use Blendhtml\Core\Auth\Entity\Session as AuthSession;
use Blendhtml\Core\Auth\Entity\User;
use Blendhtml\Core\Auth\Exception\AuthenticationException;
use Blendhtml\Core\Auth\Exception\ForbiddenException;
use Blendhtml\Core\Auth\Exception\MailDeliveryException;
use Blendhtml\Core\Auth\Exception\UnauthorizedException;
use Blendhtml\Core\Context;
use Blendhtml\Core\Logger;
use Blendhtml\Doctrine\Doctrine;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Throwable;

final class Auth
{
    private static bool $resolved = false;
    private static ?User $currentUser = null;
    private static ?AuthSession $currentSession = null;

    public static function check(): bool
    {
        self::resolveCurrentSession();

        return self::$currentUser !== null;
    }

    public static function guard(): void
    {
        self::resolveCurrentSession();

        if (self::$currentUser !== null) {
            return;
        }

        $redirect =
            SafeRedirect::sanitize(
                $_SERVER['REQUEST_URI'] ?? '/'
            ) ?? '/';

        if (
            ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
        ) {
            header(
                'Location: /login?redirect='
                . rawurlencode($redirect),
                true,
                302
            );

            exit;
        }

        http_response_code(401);

        throw new UnauthorizedException($redirect);
    }

    public static function user(): ?User
    {
        self::resolveCurrentSession();

        return self::$currentUser;
    }

    public static function id(): ?int
    {
        return self::user()?->getId();
    }

    public static function requestCode(
        string $email,
        ?string $redirect = null
    ): void {
        $normalizedEmail =
            self::normalizeAuthenticationEmail($email);

        self::assertEmailAllowed($normalizedEmail);

        $ip = ClientIp::resolve();

        RateLimiter::hit(
            'request_code',
            $normalizedEmail,
            $ip,
            Settings::requestCodeEmailRateLimit(),
            Settings::requestCodeIpRateLimit()
        );

        $length = Settings::otpLength();
        $lifetime = Settings::otpLifetime();
        $maxAttempts = Settings::otpMaxAttempts();

        $otp = str_pad(
            (string)random_int(
                0,
                (10 ** $length) - 1
            ),
            $length,
            '0',
            STR_PAD_LEFT
        );

        $codeHash = password_hash(
            $otp,
            PASSWORD_DEFAULT
        );

        if (!is_string($codeHash)) {
            throw new RuntimeException(
                'Unable to hash the verification code.'
            );
        }

        $flowSecret = self::randomSecret();
        $flowLookup = hash(
            'sha256',
            $flowSecret
        );

        $redirectPath =
            SafeRedirect::sanitize($redirect)
            ?? '/';

        $now = self::now();
        $expiresAt = $now->modify(
            '+' . $lifetime . ' seconds'
        );

        RateLimiter::prepareEmailSerialization(
            $normalizedEmail
        );

        $entityManager = Doctrine::em();
        $connection = $entityManager->getConnection();

        $connection->beginTransaction();

        try {
            RateLimiter::serializeEmail(
                $connection,
                $normalizedEmail
            );

            AuthCleanup::removeExpiredLoginCodes(
                $connection,
                $now
            );

            $existing = $entityManager
                ->getRepository(LoginCode::class)
                ->findOneBy([
                    'activeEmail' => $normalizedEmail,
                ]);

            if ($existing instanceof LoginCode) {
                $existing->invalidate($now);
            }

            $loginCode = new LoginCode(
                $normalizedEmail,
                $codeHash,
                $length,
                $maxAttempts,
                $flowLookup,
                $redirectPath,
                $now,
                $expiresAt
            );

            $entityManager->persist($loginCode);
            $entityManager->flush();
            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            Logger::event(
                'auth.storage.failure',
                [
                    'operation' => 'request_code',
                    'email_id' =>
                        Email::identifier($normalizedEmail),
                ],
                'error'
            );

            throw $exception;
        }

        $loginCodeId = $loginCode->getId();

        if ($loginCodeId === null) {
            throw new RuntimeException(
                'The verification code was not persisted.'
            );
        }
        
        try {
            $transport = Adapter::authMail()->sendOtp(
                $normalizedEmail,
                $otp,
                $lifetime
            );
        } catch (Throwable $exception) {
            Logger::event(
                'auth.mail.failure',
                [
                    'email_id' =>
                        Email::identifier($normalizedEmail),
                ],
                'error'
            );
            Logger::exception($exception);

            self::invalidatePersistedCode(
                $loginCodeId,
                $normalizedEmail
            );

            if ($exception instanceof MailDeliveryException) {
                throw $exception;
            }

            throw new MailDeliveryException($exception);
        }

        try {
            BrowserCookies::setFlow(
                $flowSecret,
                $expiresAt->getTimestamp()
            );
        } catch (Throwable $exception) {
            self::invalidatePersistedCode(
                $loginCodeId,
                $normalizedEmail
            );

            Logger::event(
                'auth.storage.failure',
                [
                    'operation' => 'set_login_flow_cookie',
                    'email_id' =>
                        Email::identifier($normalizedEmail),
                ],
                'error'
            );
            Logger::exception($exception);

            throw $exception;
        }

        Logger::event(
            'auth.login.otp_requested',
            [
                'email_id' =>
                    Email::identifier($normalizedEmail),
                'ip' => $ip,
                'transport' => $transport,
                'expires_at' =>
                    $expiresAt->format(DATE_ATOM),
            ]
        );
    }

    public static function verifyCode(
        string $email,
        string $code
    ): User {
        $normalizedEmail =
            self::normalizeAuthenticationEmail($email);

        self::assertEmailAllowed($normalizedEmail);

        $ip = ClientIp::resolve();

        RateLimiter::hit(
            'verify_code',
            $normalizedEmail,
            $ip,
            Settings::verifyCodeEmailRateLimit(),
            Settings::verifyCodeIpRateLimit()
        );

        RateLimiter::prepareEmailSerialization(
            $normalizedEmail
        );

        $submittedCode = trim($code);
        $sessionLifetime = Settings::sessionLifetime();
        $now = self::now();

        $entityManager = Doctrine::em();
        $connection = $entityManager->getConnection();

        $authenticatedUser = null;
        $createdSession = null;
        $sessionSecret = null;
        $committed = false;

        $connection->beginTransaction();

        try {
            RateLimiter::serializeEmail(
                $connection,
                $normalizedEmail
            );

            $loginCode = $entityManager
                ->getRepository(LoginCode::class)
                ->findOneBy([
                    'activeEmail' => $normalizedEmail,
                ]);

            if (!$loginCode instanceof LoginCode) {
                $latest = $entityManager
                    ->getRepository(LoginCode::class)
                    ->findOneBy(
                        [
                            'email' => $normalizedEmail,
                        ],
                        [
                            'createdAt' => 'DESC',
                        ]
                    );

                $connection->commit();
                $committed = true;

                throw self::inactiveCodeException(
                    $latest,
                    $normalizedEmail,
                    $now
                );
            }

            if ($loginCode->getExpiresAt() <= $now) {
                $loginCode->invalidate($now);
                $entityManager->flush();
                $connection->commit();
                $committed = true;

                Logger::event(
                    'auth.login.otp_expired',
                    [
                        'email_id' =>
                            Email::identifier($normalizedEmail),
                        'ip' => $ip,
                    ],
                    'notice'
                );

                throw new AuthenticationException(
                    AuthenticationException::EXPIRED_CODE,
                    'The verification code has expired. Request a new code.',
                    400
                );
            }

            if (
                $loginCode->getAttempts()
                >= $loginCode->getMaxAttempts()
            ) {
                $loginCode->invalidate($now);
                $entityManager->flush();
                $connection->commit();
                $committed = true;

                Logger::event(
                    'auth.login.otp_attempts_exceeded',
                    [
                        'email_id' =>
                            Email::identifier($normalizedEmail),
                        'ip' => $ip,
                    ],
                    'warning'
                );

                throw new AuthenticationException(
                    AuthenticationException::MAXIMUM_ATTEMPTS,
                    'The verification code is no longer valid. Request a new code.',
                    400
                );
            }

            $formatValid =
                strlen($submittedCode)
                === $loginCode->getCodeLength()
                && ctype_digit($submittedCode);

            $verified =
                $formatValid
                && password_verify(
                    $submittedCode,
                    $loginCode->getCodeHash()
                );

            if (!$verified) {
                $maximumReached =
                    $loginCode->fail($now);

                $entityManager->flush();
                $connection->commit();
                $committed = true;

                Logger::event(
                    $maximumReached
                        ? 'auth.login.otp_attempts_exceeded'
                        : 'auth.login.otp_invalid',
                    [
                        'email_id' =>
                            Email::identifier($normalizedEmail),
                        'ip' => $ip,
                    ],
                    $maximumReached
                        ? 'warning'
                        : 'notice'
                );

                throw new AuthenticationException(
                    $maximumReached
                        ? AuthenticationException::MAXIMUM_ATTEMPTS
                        : AuthenticationException::INVALID_CODE,
                    $maximumReached
                        ? 'The verification code is no longer valid. Request a new code.'
                        : 'The verification code is invalid.',
                    400
                );
            }

            $loginCode->consume($now);

            $user = $entityManager
                ->getRepository(User::class)
                ->findOneBy([
                    'email' => $normalizedEmail,
                ]);

            if (!$user instanceof User) {
                $user = new User(
                    $normalizedEmail,
                    $now
                );

                $entityManager->persist($user);
            } else {
                $connection->executeStatement(
                    'UPDATE auth__users '
                    . 'SET updated_at = updated_at '
                    . 'WHERE id = ?',
                    [
                        $user->getId(),
                    ]
                );

                $user->touch($now);
            }

            $sessionSecret = self::randomSecret();
            $sessionHash = password_hash(
                $sessionSecret,
                PASSWORD_DEFAULT
            );

            if (!is_string($sessionHash)) {
                throw new RuntimeException(
                    'Unable to hash the browser session.'
                );
            }

            $sessionExpiresAt = $now->modify(
                '+' . $sessionLifetime . ' seconds'
            );

            AuthCleanup::removeExpiredSessions(
                $connection,
                $now
            );

            $createdSession = new AuthSession(
                $user,
                hash('sha256', $sessionSecret),
                $sessionHash,
                $now,
                $sessionExpiresAt
            );

            $entityManager->persist($createdSession);
            $entityManager->flush();
            $connection->commit();
            $committed = true;

            $authenticatedUser = $user;
        } catch (Throwable $exception) {
            if (
                !$committed
                && $connection->isTransactionActive()
            ) {
                $connection->rollBack();
            }

            if (!$exception instanceof AuthenticationException) {
                Logger::event(
                    'auth.storage.failure',
                    [
                        'operation' => 'verify_code',
                        'email_id' =>
                            Email::identifier($normalizedEmail),
                    ],
                    'error'
                );
            }

            throw $exception;
        }

        if (
            !$authenticatedUser instanceof User
            || !$createdSession instanceof AuthSession
            || !is_string($sessionSecret)
        ) {
            throw new RuntimeException(
                'Authentication transaction did not create a session.'
            );
        }

        try {
            BrowserCookies::setAuth(
                $sessionSecret,
                $createdSession
                    ->getExpiresAt()
                    ->getTimestamp()
            );
        } catch (Throwable $exception) {
            self::revokeSessionAfterCookieFailure(
                $createdSession
            );

            Logger::event(
                'auth.storage.failure',
                [
                    'operation' => 'set_auth_cookie',
                    'user_id' =>
                        $authenticatedUser->getId(),
                ],
                'error'
            );
            Logger::exception($exception);

            throw $exception;
        }

        BrowserCookies::clearFlow();

        self::$resolved = true;
        self::$currentUser = $authenticatedUser;
        self::$currentSession = $createdSession;

        Logger::event(
            'auth.login.success',
            [
                'user_id' => $authenticatedUser->getId(),
                'session_id' => $createdSession->getId(),
                'email_id' =>
                    Email::identifier($normalizedEmail),
                'ip' => $ip,
            ]
        );

        return $authenticatedUser;
    }

    public static function pendingLogin(): ?array
    {
        $flowSecret = BrowserCookies::flowSecret();

        if (
            !is_string($flowSecret)
            || preg_match(
                '/^[A-Za-z0-9_-]{43}$/',
                $flowSecret
            ) !== 1
        ) {
            BrowserCookies::clearFlow();

            return null;
        }

        $lookup = hash('sha256', $flowSecret);

        $loginCode = Doctrine::em()
            ->getRepository(LoginCode::class)
            ->findOneBy([
                'flowLookup' => $lookup,
            ]);

        $now = self::now();

        if (
            !$loginCode instanceof LoginCode
            || !hash_equals(
                $loginCode->getFlowLookup(),
                $lookup
            )
            || $loginCode->getConsumedAt() !== null
            || $loginCode->getInvalidatedAt() !== null
            || $loginCode->getActiveEmail()
            !== $loginCode->getEmail()
        ) {
            BrowserCookies::clearFlow();

            return null;
        }

        if ($loginCode->getExpiresAt() <= $now) {
            $id = $loginCode->getId();

            if ($id !== null) {
                self::invalidatePersistedCode(
                    $id,
                    $loginCode->getEmail()
                );
            }

            BrowserCookies::clearFlow();

            Logger::event(
                'auth.login.otp_expired',
                [
                    'email_id' =>
                        Email::identifier(
                            $loginCode->getEmail()
                        ),
                ],
                'notice'
            );

            return null;
        }

        return [
            'email' => $loginCode->getEmail(),
            'redirect' =>
                SafeRedirect::sanitize(
                    $loginCode->getRedirectPath()
                ) ?? '/',
            'code_length' =>
                $loginCode->getCodeLength(),
            'expires_at' =>
                $loginCode->getExpiresAt(),
        ];
    }

    public static function logout(
        ?string $redirect = null
    ): never {
        $user = self::user();
        $userId = $user?->getId();

        if ($userId !== null) {
            $entityManager = Doctrine::em();
            $connection = $entityManager->getConnection();
            $now = self::now();

            $connection->beginTransaction();

            try {
                $connection->executeStatement(
                    'UPDATE auth__users '
                    . 'SET updated_at = updated_at '
                    . 'WHERE id = ?',
                    [
                        $userId,
                    ]
                );

                $connection->executeStatement(
                    'UPDATE auth__sessions '
                    . 'SET revoked_at = ? '
                    . 'WHERE user_id = ? '
                    . 'AND revoked_at IS NULL',
                    [
                        $now->format('Y-m-d H:i:s'),
                        $userId,
                    ]
                );

                $connection->commit();
            } catch (Throwable $exception) {
                if ($connection->isTransactionActive()) {
                    $connection->rollBack();
                }

                Logger::event(
                    'auth.storage.failure',
                    [
                        'operation' => 'logout',
                        'user_id' => $userId,
                    ],
                    'error'
                );

                throw $exception;
            }
        }

        BrowserCookies::clearAuth();

        self::$resolved = true;
        self::$currentUser = null;
        self::$currentSession = null;

        Logger::event(
            'auth.logout',
            [
                'user_id' => $userId,
            ]
        );

        $requestedRedirect = $redirect;

        if (
            $requestedRedirect === null
            && isset($_GET['redirect'])
            && is_string($_GET['redirect'])
        ) {
            $requestedRedirect = $_GET['redirect'];
        }

        $target =
            SafeRedirect::sanitize($requestedRedirect)
            ?? '/';

        if (headers_sent()) {
            throw new RuntimeException(
                'Logout redirect must be sent before response output.'
            );
        }

        header(
            'Location: ' . $target,
            true,
            302
        );

        exit;
    }

    public static function csrfToken(): string
    {
        return Csrf::token();
    }

    public static function assertCsrfToken(
        mixed $submitted
    ): void {
        Csrf::assert($submitted);
    }

    public static function roles(): array
    {
        $user = self::user();

        return $user instanceof User
            ? $user->roleNames()
            : [];
    }

    public static function hasRole(string $role): bool
    {
        $role = RoleName::normalize($role);

        return in_array(
            $role,
            self::roles(),
            true
        );
    }

    public static function hasAnyRole(array $roles): bool
    {
        if (!self::check()) {
            return false;
        }

        foreach ($roles as $role) {
            if (
                !is_string($role)
                || !$role
            ) {
                if (!is_string($role)) {
                    throw new \InvalidArgumentException(
                        'Roles must be strings.'
                    );
                }
            }

            if (self::hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public static function hasAllRoles(array $roles): bool
    {
        if (!self::check()) {
            return false;
        }

        foreach ($roles as $role) {
            if (!is_string($role)) {
                throw new \InvalidArgumentException(
                    'Roles must be strings.'
                );
            }

            if (!self::hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    public static function requireRole(string $role): void
    {
        $role = RoleName::normalize($role);

        if (!self::check()) {
            $redirect =
                SafeRedirect::sanitize(
                    $_SERVER['REQUEST_URI'] ?? '/'
                ) ?? '/';

            Logger::event(
                'auth.authorization.unauthenticated',
                [
                    'role' => $role,
                ],
                'notice'
            );

            throw new UnauthorizedException($redirect);
        }

        if (!self::hasRole($role)) {
            Logger::event(
                'auth.authorization.forbidden',
                [
                    'user_id' => self::id(),
                    'role' => $role,
                ],
                'warning'
            );

            throw new ForbiddenException();
        }
    }

    private static function resolveCurrentSession(): void
    {
        if (self::$resolved) {
            return;
        }

        $secret = BrowserCookies::authSecret();

        if (
            !is_string($secret)
            || preg_match(
                '/^[A-Za-z0-9_-]{43}$/',
                $secret
            ) !== 1
        ) {
            if ($secret !== null) {
                BrowserCookies::clearAuth();
            }

            self::$resolved = true;

            return;
        }

        $lookup = hash('sha256', $secret);
        $entityManager = Doctrine::em();

        $session = $entityManager
            ->getRepository(AuthSession::class)
            ->findOneBy([
                'secretLookup' => $lookup,
            ]);

        $now = self::now();

        AuthCleanup::removeExpiredSessions(
            $entityManager->getConnection(),
            $now
        );

        if (
            !$session instanceof AuthSession
            || !hash_equals(
                $session->getSecretLookup(),
                $lookup
            )
            || !password_verify(
                $secret,
                $session->getSecretHash()
            )
        ) {
            BrowserCookies::clearAuth();
            self::$resolved = true;

            return;
        }

        if ($session->getRevokedAt() !== null) {
            Logger::event(
                'auth.session.revoked',
                [
                    'session_id' => $session->getId(),
                ],
                'notice'
            );

            BrowserCookies::clearAuth();
            self::$resolved = true;

            return;
        }

        if ($session->getExpiresAt() <= $now) {
            Logger::event(
                'auth.session.expired',
                [
                    'session_id' => $session->getId(),
                ],
                'notice'
            );

            BrowserCookies::clearAuth();
            self::$resolved = true;

            return;
        }

        if (
            $now->getTimestamp()
            - $session
                ->getLastUsedAt()
                ->getTimestamp()
            >= 300
        ) {
            $session->touch($now);
            $entityManager->flush();
        }

        self::$resolved = true;
        self::$currentSession = $session;
        self::$currentUser = $session->getUser();
    }

    private static function normalizeAuthenticationEmail(
        string $email
    ): string {
        try {
            return Email::normalize($email);
        } catch (\InvalidArgumentException) {
            Logger::event(
                'auth.login.invalid_email',
                [],
                'notice'
            );

            throw new AuthenticationException(
                AuthenticationException::INVALID_EMAIL,
                'Please enter a valid email address.',
                422
            );
        }
    }

    private static function assertEmailAllowed(
        string $email
    ): void {
        if (Settings::emailAccessMode() !== 'whitelist') {
            return;
        }

        $exists = Doctrine::em()
            ->getConnection()
            ->fetchOne(
                'SELECT 1 FROM auth__email_whitelist '
                . 'WHERE email = ?',
                [
                    $email,
                ]
            );

        if ($exists !== false) {
            return;
        }

        Logger::event(
            'auth.login.email_rejected',
            [
                'email_id' => Email::identifier($email),
                'ip' => ClientIp::resolve(),
            ],
            'notice'
        );

        throw new AuthenticationException(
            AuthenticationException::EMAIL_NOT_AUTHORIZED,
            'This email address is not authorized to access this application.',
            403
        );
    }

    private static function inactiveCodeException(
        mixed $latest,
        string $email,
        DateTimeImmutable $now
    ): AuthenticationException {
        $event = 'auth.login.otp_unavailable';
        $reason = AuthenticationException::INVALID_CODE;
        $message = 'The verification code is invalid.';

        $context = [
            'email_id' => Email::identifier($email),
        ];

        if ($latest instanceof LoginCode) {
            $context['created_at'] =
                $latest->getCreatedAt()->format(DATE_ATOM);

            $context['expires_at'] =
                $latest->getExpiresAt()->format(DATE_ATOM);

            $context['now'] =
                $now->format(DATE_ATOM);

            if ($latest->getConsumedAt() !== null) {
                $event = 'auth.login.otp_consumed';
                $reason = AuthenticationException::CONSUMED_CODE;
                $message =
                    'The verification code has already been used.';
            } elseif (
                $latest->getInvalidatedAt() !== null
                && $latest->getAttempts()
                >= $latest->getMaxAttempts()
            ) {
                $event = 'auth.login.otp_attempts_exceeded';
                $reason =
                    AuthenticationException::MAXIMUM_ATTEMPTS;
                $message =
                    'The verification code is no longer valid. Request a new code.';
            } elseif (
                $latest->getInvalidatedAt() !== null
            ) {
                $event = 'auth.login.otp_invalidated';
                $reason =
                    AuthenticationException::INVALIDATED_CODE;
                $message =
                    'The verification code is no longer valid. Request a new code.';
            } elseif ($latest->getExpiresAt() <= $now) {
                $event = 'auth.login.otp_expired';
                $reason =
                    AuthenticationException::EXPIRED_CODE;
                $message =
                    'The verification code has expired. Request a new code.';
            }
        }

        Logger::event(
            $event,
            $context,
            $reason === AuthenticationException::INVALID_CODE
                ? 'notice'
                : 'warning'
        );

        return new AuthenticationException(
            $reason,
            $message,
            400
        );
    }

    private static function invalidatePersistedCode(
        int $loginCodeId,
        string $email
    ): void {
        RateLimiter::prepareEmailSerialization($email);

        $entityManager = Doctrine::em();
        $connection = $entityManager->getConnection();

        $connection->beginTransaction();

        try {
            RateLimiter::serializeEmail(
                $connection,
                $email
            );

            $loginCode = $entityManager->find(
                LoginCode::class,
                $loginCodeId
            );

            if (
                $loginCode instanceof LoginCode
                && $loginCode->getActiveEmail() !== null
            ) {
                $loginCode->invalidate(self::now());
                $entityManager->flush();
            }

            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            Logger::event(
                'auth.storage.failure',
                [
                    'operation' => 'invalidate_login_code',
                    'email_id' => Email::identifier($email),
                ],
                'error'
            );
            Logger::exception($exception);

            throw $exception;
        }
    }

    private static function revokeSessionAfterCookieFailure(
        AuthSession $session
    ): void {
        $sessionId = $session->getId();

        if ($sessionId === null) {
            return;
        }

        Doctrine::em()
            ->getConnection()
            ->executeStatement(
                'UPDATE auth__sessions '
                . 'SET revoked_at = ? '
                . 'WHERE id = ? AND revoked_at IS NULL',
                [
                    self::now()->format('Y-m-d H:i:s'),
                    $sessionId,
                ]
            );
    }

    private static function randomSecret(): string
    {
        return rtrim(
            strtr(
                base64_encode(random_bytes(32)),
                '+/',
                '-_'
            ),
            '='
        );
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            'now',
            new DateTimeZone('UTC')
        );
    }
}
