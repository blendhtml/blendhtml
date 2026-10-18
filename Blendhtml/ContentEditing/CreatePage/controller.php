<?php

declare(strict_types=1);

use Blendhtml\Core\Auth\Auth;

$homepageExists = false;

$allowedErrors = [
    'invalid_csrf',
    'invalid_url',
    'invalid_languages',
    'invalid_prompt',
    'rate_limit'
];

$error = is_string($_GET['error'] ?? null)
    && in_array($_GET['error'], $allowedErrors, true)
        ? $_GET['error']
        : null;

return [
    'csrfToken' => Auth::csrfToken(),
    'homepageExists' => $homepageExists,
    'desiredUrl' => $homepageExists ? '' : '/',
    'selectedLanguages' => ['et'],
    'prompt' => '',
    'submitted' => ($_GET['submitted'] ?? null) === '1',
    'error' => $error,
];
