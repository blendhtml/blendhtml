<?php

declare(strict_types=1);

use Blendhtml\Core\Auth\Auth;
use Blendhtml\Core\Auth\Exception\AuthenticationException;
use Blendhtml\Core\Auth\Exception\MailDeliveryException;
use Blendhtml\Core\Auth\SafeRedirect;
use Blendhtml\Core\Context;
use Blendhtml\Core\Logger;

if (Context::page() !== 'Login') {
    return [];
}

$method = strtoupper(
    $_SERVER['REQUEST_METHOD'] ?? 'GET'
);

$redirect = SafeRedirect::sanitize(
    $method === 'POST'
        ? ($_POST['redirect'] ?? null)
        : ($_GET['redirect'] ?? null)
) ?? '/';

$email = '';
$error = null;

//if ($method === 'GET' && Auth::check()) {
//    if (!headers_sent()) {
//        header(
//            'Location: ' . $redirect,
//            true,
//            302
//        );
//
//        exit;
//    }
//}

if ($method === 'POST') {
    $email = is_string($_POST['email'] ?? null)
        ? trim($_POST['email'])
        : '';

    try {
        Auth::assertCsrfToken(
            $_POST['_csrf'] ?? null
        );

        Auth::requestCode(
            $email,
            $redirect
        );

        if (headers_sent()) {
            throw new RuntimeException(
                'Verification redirect must be sent before response output.'
            );
        }

        header(
            'Location: /login/verify',
            true,
            303
        );

        exit;
    } catch (MailDeliveryException $exception) {
        http_response_code(503);

        Logger::exception($exception);

        $error = 'mail_delivery';
    } catch (AuthenticationException $exception) {
        http_response_code($exception->status());

        if ($exception->retryAfter() !== null) {
            header(
                'Retry-After: '
                . $exception->retryAfter()
            );
        }

        $error = match ($exception->reason()) {
            AuthenticationException::INVALID_EMAIL
            => 'invalid_email',

            AuthenticationException::EMAIL_NOT_AUTHORIZED
            => 'email_not_authorized',

            AuthenticationException::RATE_LIMITED
            => 'rate_limited',

            AuthenticationException::INVALID_CSRF
            => 'invalid_csrf',

            default
            => 'authentication_error',
        };
    }
} elseif ($method !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');

    $error = 'method_not_allowed';
}

return [
    'csrfToken' => Auth::csrfToken(),
    'email' => $email,
    'redirect' => $redirect,
    'error' => $error,
];