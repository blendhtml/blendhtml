<?php

declare(strict_types=1);

use Blendhtml\Core\Auth\Auth;
use Blendhtml\Core\Auth\BlendhtmlRoles;
use Blendhtml\Core\Auth\Exception\AuthenticationException;
use Blendhtml\Core\Auth\Exception\ForbiddenException;
use Blendhtml\Core\Logger;

Auth::guard();

if (!Auth::hasRole(BlendhtmlRoles::CONTENT_EDITING)) {
    throw new ForbiddenException();
}

$redirect = static function (array $query = []): never {
    $location = '/blendhtml/content-editing/create-page';

    if ($query !== []) {
        $location .= '?' . http_build_query($query);
    }

    header('Location: ' . $location, true, 303);
    exit;
};

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    return [];
}

try {
    Auth::assertCsrfToken($_POST['_csrf'] ?? null);
} catch (AuthenticationException) {
    $redirect([
        'error' => 'invalid_csrf',
    ]);
}

$homepageExists = false;

$url = is_string($_POST['url'] ?? null)
    ? trim($_POST['url'])
    : '';

$urlIsValid = strlen($url) <= 2048
    && preg_match(
        '#^/(?:[a-z0-9]+(?:-[a-z0-9]+)*(?:/[a-z0-9]+(?:-[a-z0-9]+)*)*)?$#D',
        $url
    ) === 1;

if (!$homepageExists && $url !== '/') {
    $urlIsValid = false;
}

if (!$urlIsValid) {
    $redirect([
        'error' => 'invalid_url',
    ]);
}

// @todo Uncomment when language selection logic time comes
/*
$submittedLanguages = $_POST['languages'] ?? [];

if (!is_array($submittedLanguages)) {
    $redirect([
        'error' => 'invalid_languages',
    ]);
}

$allowedLanguages = [
    'et',
    'en',
    'ru',
];

$languages = [];

foreach ($submittedLanguages as $language) {
    if (
        !is_string($language)
        || !in_array($language, $allowedLanguages, true)
    ) {
        $redirect([
            'error' => 'invalid_languages',
        ]);
    }

    $languages[$language] = true;
}

$languages = array_keys($languages);

if ($languages === []) {
    $redirect([
        'error' => 'invalid_languages',
    ]);
}
*/

$prompt = is_string($_POST['prompt'] ?? null)
    ? trim($_POST['prompt'])
    : '';

if ($prompt === '' || strlen($prompt) > 50000) {
    $redirect([
        'error' => 'invalid_prompt',
    ]);
}

$email = Auth::user()->getEmail();

if (!is_string($email) || $email === '') {
    throw new RuntimeException('The authenticated user does not have an email address.');
}

$aiDirectory = dirname(__DIR__, 7) . '/ai';
$rateLimiterFile = $aiDirectory . '/email-rate-limiter.json';
$promptFile = $aiDirectory . '/ai-prompt.md';

$rateLimiterHandle = fopen($rateLimiterFile, 'c+');

if ($rateLimiterHandle === false) {
    throw new RuntimeException('Unable to open the email rate limiter file.');
}

$emailRequestCount = 0;

try {
    if (!flock($rateLimiterHandle, LOCK_EX)) {
        throw new RuntimeException('Unable to lock the email rate limiter file.');
    }

    rewind($rateLimiterHandle);

    $rateLimiterContents = stream_get_contents($rateLimiterHandle);

    if ($rateLimiterContents === false) {
        throw new RuntimeException('Unable to read the email rate limiter file.');
    }

    if (trim($rateLimiterContents) === '') {
        $rateLimiterEmails = [];
    } else {
        $rateLimiterEmails = json_decode(
            $rateLimiterContents,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_array($rateLimiterEmails) || !array_is_list($rateLimiterEmails)) {
            throw new RuntimeException('The email rate limiter file is invalid.');
        }

        foreach ($rateLimiterEmails as $rateLimiterEmail) {
            if (!is_string($rateLimiterEmail)) {
                throw new RuntimeException('The email rate limiter file is invalid.');
            }
        }
    }

    $rateLimiterEmails[] = $email;
    $emailRequestCount = count(
        array_keys($rateLimiterEmails, $email, true)
    );

    $encodedRateLimiterEmails = json_encode(
            $rateLimiterEmails,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ) . PHP_EOL;

    rewind($rateLimiterHandle);

    if (!ftruncate($rateLimiterHandle, 0)) {
        throw new RuntimeException('Unable to truncate the email rate limiter file.');
    }

    $bytesWritten = fwrite($rateLimiterHandle, $encodedRateLimiterEmails);

    if (
        $bytesWritten === false
        || $bytesWritten !== strlen($encodedRateLimiterEmails)
    ) {
        throw new RuntimeException('Unable to write the email rate limiter file.');
    }

    if (!fflush($rateLimiterHandle)) {
        throw new RuntimeException('Unable to flush the email rate limiter file.');
    }
} finally {
    flock($rateLimiterHandle, LOCK_UN);
    fclose($rateLimiterHandle);
}

if ($emailRequestCount > 5) {
    $redirect([
        'error' => 'rate_limit',
    ]);
}

if (file_put_contents($promptFile, $prompt, LOCK_EX) === false) {
    throw new RuntimeException('Unable to write the AI prompt file.');
}

$runScript = $aiDirectory . '/run.inform.php';

if (!is_file($runScript)) {
    throw new RuntimeException(
        'AI runner script does not exist.'
    );
}

$command = sprintf(
    '%s %s --email=%s --url=%s > /dev/null 2>&1 &',
    escapeshellarg(PHP_BINARY),
    escapeshellarg($runScript),
    escapeshellarg($email),
    escapeshellarg($url)
);

exec($command);

Logger::event(
    'content_editing.page_creation_requested',
    [
        'user_id' => Auth::id(),
        'desired_url' => $url,
        // @todo Uncomment when language selection logic time comes
        // 'languages' => $languages,
        'prompt_length' => strlen($prompt),
        // 'requires_confirmation' => true,
    ]
);

if ($emailRequestCount === 5) {
    $redirect([
        'submitted' => '1',
        'error' => 'rate_limit',
    ]);
}

$redirect([
    'submitted' => '1',
]);