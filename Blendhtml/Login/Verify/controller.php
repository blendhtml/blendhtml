<?php

declare(strict_types=1);

use Blendhtml\Core\Auth\Auth;
use Blendhtml\Core\Auth\Exception\AuthenticationException;

$method = strtoupper(
    $_SERVER['REQUEST_METHOD'] ?? 'GET'
);

$pending = Auth::pendingLogin();
$error = null;

if ($pending === null) {
    $error = AuthenticationException::MISSING_LOGIN_FLOW;
}

if ($method === 'POST') {
    try {
        Auth::assertCsrfToken(
            $_POST['_csrf'] ?? null
        );

        if ($pending === null) {
            throw new AuthenticationException(
                AuthenticationException::MISSING_LOGIN_FLOW,
                'This verification request is no longer valid. Request a new code.',
                400
            );
        }

        $code = is_string($_POST['code'] ?? null)
            ? $_POST['code']
            : '';

        Auth::verifyCode(
            $pending['email'],
            $code
        );

        if (headers_sent()) {
            throw new RuntimeException(
                'Login redirect must be sent before response output.'
            );
        }

        header(
            'Location: ' . $pending['redirect'],
            true,
            303
        );

        exit;
    } catch (AuthenticationException $exception) {
        http_response_code($exception->status());

        if ($exception->retryAfter() !== null) {
            header(
                'Retry-After: '
                . $exception->retryAfter()
            );
        }

        $error = $exception->reason();
    }
} elseif ($method !== 'GET') {
    http_response_code(405);

    header('Allow: GET, POST');

    $error = 'method_not_allowed';
}

$redirect = $pending['redirect'] ?? '/';

return [
    'csrfToken' => Auth::csrfToken(),
    'error' => $error,
    'pending' => $pending !== null,
    'codeLength' => $pending['code_length'] ?? 6,
    'restartUrl' =>
        '/login?redirect='
        . rawurlencode($redirect),
];