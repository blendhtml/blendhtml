<?php

declare(strict_types=1);

use Blendhtml\Core\Auth\Auth;
use Blendhtml\Core\Auth\AuthAdmin;
use Blendhtml\Core\Auth\BlendhtmlRoles;
use Blendhtml\Core\Auth\Entity\User;
use Blendhtml\Core\Auth\Exception\AuthenticationException;
use Blendhtml\Doctrine\Doctrine;

const MANAGE_USER_ACCESS_PER_PAGE = 25;

$method = strtoupper(
    $_SERVER['REQUEST_METHOD'] ?? 'GET'
);

$parsePage = static function (mixed $value): int {
    if (
        is_int($value)
        && $value > 0
    ) {
        return $value;
    }

    if (
        is_string($value)
        && ctype_digit($value)
        && (int)$value > 0
    ) {
        return (int)$value;
    }

    return 1;
};

$pageNumber = $parsePage(
    $method === 'POST'
        ? ($_POST['return_page'] ?? null)
        : ($_GET['page'] ?? null)
);

$actionAreas = [
    'set_mode' => 'mode',
    'add_whitelist' => 'whitelist',
    'remove_whitelist' => 'whitelist',
    'create_role' => 'roles',
    'delete_role' => 'roles',
    'create_user' => 'users',
    'sync_user_roles' => 'users',
    'delete_user' => 'users',
];

$noticeAreas = [
    'mode_updated' => 'mode',
    'whitelist_updated' => 'whitelist',
    'role_created' => 'roles',
    'role_deleted' => 'roles',
    'user_created' => 'users',
    'user_roles_updated' => 'users',
    'user_deleted' => 'users',
];

$notice = is_string($_GET['notice'] ?? null)
    && isset($noticeAreas[$_GET['notice']])
        ? $_GET['notice']
        : null;

$noticeArea = $notice !== null
    ? $noticeAreas[$notice]
    : null;

$error = null;
$errorArea = null;

if ($method === 'POST') {
    $action = is_string($_POST['action'] ?? null)
        ? $_POST['action']
        : '';

    $errorArea = $actionAreas[$action] ?? 'mode';

    try {
        Auth::assertCsrfToken(
            $_POST['_csrf'] ?? null
        );

        $successNotice = match ($action) {
            'set_mode' => (static function (): string {
                $mode = is_string($_POST['mode'] ?? null)
                    ? $_POST['mode']
                    : '';

                AuthAdmin::setEmailAccessMode($mode);

                return 'mode_updated';
            })(),

            'add_whitelist' => (static function (): string {
                $email = is_string($_POST['email'] ?? null)
                    ? $_POST['email']
                    : '';

                AuthAdmin::addEmailToWhitelist($email);

                return 'whitelist_updated';
            })(),

            'remove_whitelist' => (static function (): string {
                $email = is_string($_POST['email'] ?? null)
                    ? $_POST['email']
                    : '';

                AuthAdmin::removeEmailFromWhitelist($email);

                return 'whitelist_updated';
            })(),

            'create_role' => (static function (): string {
                $role = is_string($_POST['role'] ?? null)
                    ? $_POST['role']
                    : '';

                AuthAdmin::createRole($role);

                return 'role_created';
            })(),

            'delete_role' => (static function (): string {
                $role = is_string($_POST['role'] ?? null)
                    ? $_POST['role']
                    : '';

                AuthAdmin::deleteRole($role);

                return 'role_deleted';
            })(),

            'create_user' => (static function (): string {
                $email = is_string($_POST['email'] ?? null)
                    ? $_POST['email']
                    : '';

                AuthAdmin::createUser($email);

                return 'user_created';
            })(),

            'sync_user_roles' => (static function (): string {
                $userIdValue = $_POST['user_id'] ?? null;

                if (
                    !is_string($userIdValue)
                    || !ctype_digit($userIdValue)
                    || (int)$userIdValue < 1
                ) {
                    throw new InvalidArgumentException(
                        'A valid user is required.'
                    );
                }

                $userId = (int)$userIdValue;
                $entityManager = Doctrine::em();
                $user = $entityManager->find(
                    User::class,
                    $userId
                );

                if (!$user instanceof User) {
                    throw new InvalidArgumentException(
                        'The selected user no longer exists.'
                    );
                }

                $submittedRoles = $_POST['roles'] ?? [];

                if (!is_array($submittedRoles)) {
                    throw new InvalidArgumentException(
                        'The submitted roles are invalid.'
                    );
                }

                $availableRoles = AuthAdmin::getRoles();
                $roles = [];

                foreach ($submittedRoles as $role) {
                    if (
                        !is_string($role)
                        || !in_array($role, $availableRoles, true)
                    ) {
                        throw new InvalidArgumentException(
                            'One or more selected roles no longer exist.'
                        );
                    }

                    $roles[$role] = true;
                }

                AuthAdmin::syncRoles(
                    $userId,
                    array_keys($roles)
                );

                return 'user_roles_updated';
            })(),

            'delete_user' => (static function (): string {
                $userIdValue = $_POST['user_id'] ?? null;

                if (
                    !is_string($userIdValue)
                    || !ctype_digit($userIdValue)
                    || (int)$userIdValue < 1
                ) {
                    throw new InvalidArgumentException(
                        'A valid user is required.'
                    );
                }

                $entityManager = Doctrine::em();
                $user = $entityManager->find(
                    User::class,
                    (int)$userIdValue
                );

                if (!$user instanceof User) {
                    throw new InvalidArgumentException(
                        'The selected user no longer exists.'
                    );
                }

                $entityManager->remove($user);
                $entityManager->flush();

                return 'user_deleted';
            })(),

            default => throw new InvalidArgumentException(
                'The requested operation is invalid.'
            ),
        };

        $requestPath = parse_url(
            $_SERVER['REQUEST_URI']
                ?? '/blendhtml/admin/manage-user-access',
            PHP_URL_PATH
        );

        if (
            !is_string($requestPath)
            || !str_starts_with($requestPath, '/')
            || str_starts_with($requestPath, '//')
        ) {
            $requestPath =
                '/blendhtml/admin/manage-user-access';
        }

        $query = [
            'notice' => $successNotice,
        ];

        if ($pageNumber > 1) {
            $query['page'] = $pageNumber;
        }

        header(
            'Location: '
            . $requestPath
            . '?'
            . http_build_query($query),
            true,
            303
        );

        exit;
    } catch (AuthenticationException $exception) {
        http_response_code($exception->status());
        $error = $exception->safeMessage();
    } catch (InvalidArgumentException $exception) {
        http_response_code(422);
        $error = $exception->getMessage();
    }
} elseif ($method !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');

    $error = 'This request method is not supported.';
    $errorArea = 'mode';
}

$entityManager = Doctrine::em();

$totalUsers = (int)$entityManager
    ->createQueryBuilder()
    ->select('COUNT(authUser.id)')
    ->from(User::class, 'authUser')
    ->getQuery()
    ->getSingleScalarResult();

$pageCount = max(
    1,
    (int)ceil(
        $totalUsers / MANAGE_USER_ACCESS_PER_PAGE
    )
);

$pageNumber = min($pageNumber, $pageCount);

$userEntities = $entityManager
    ->getRepository(User::class)
    ->createQueryBuilder('authUser')
    ->orderBy('authUser.email', 'ASC')
    ->setFirstResult(
        ($pageNumber - 1)
        * MANAGE_USER_ACCESS_PER_PAGE
    )
    ->setMaxResults(MANAGE_USER_ACCESS_PER_PAGE)
    ->getQuery()
    ->getResult();

$users = array_map(
    static fn(User $user): array => [
        'id' => $user->getId(),
        'email' => $user->getEmail(),
        'roles' => $user->roleNames(),
    ],
    $userEntities
);

$roleNames = AuthAdmin::getRoles();

$roleUserEntities = $totalUsers <= MANAGE_USER_ACCESS_PER_PAGE
    ? $userEntities
    : $entityManager->getRepository(User::class)->findAll();

$boundRoles = [];

foreach ($roleUserEntities as $roleUser) {
    if (!$roleUser instanceof User) {
        continue;
    }

    foreach ($roleUser->roleNames() as $roleName) {
        $boundRoles[$roleName] = true;
    }
}

$roles = array_map(
    static fn(string $role): array => [
        'name' => $role,
        'protected' =>
            BlendhtmlRoles::isProtected($role),
        'bound' => isset($boundRoles[$role]),
    ],
    $roleNames
);

$paginationStart = max(1, $pageNumber - 2);
$paginationEnd = min($pageCount, $pageNumber + 2);

if ($paginationEnd - $paginationStart < 4) {
    $paginationStart = max(
        1,
        $paginationEnd - 4
    );

    $paginationEnd = min(
        $pageCount,
        $paginationStart + 4
    );
}

$paginationPages = range(
    $paginationStart,
    $paginationEnd
);

$pageUrl = static fn(int $page): string =>
    '/blendhtml/admin/manage-user-access'
    . ($page > 1 ? '?page=' . $page : '');

$showingFrom = $totalUsers === 0
    ? 0
    : (($pageNumber - 1)
        * MANAGE_USER_ACCESS_PER_PAGE) + 1;

$showingTo = min(
    $totalUsers,
    $pageNumber * MANAGE_USER_ACCESS_PER_PAGE
);

return [
    'csrfToken' => Auth::csrfToken(),
    'mode' => AuthAdmin::getEmailAccessMode(),
    'whitelist' => AuthAdmin::getEmailWhitelist(),
    'roles' => $roles,
    'roleNames' => $roleNames,
    'users' => $users,
    'currentUserId' => Auth::id(),
    'error' => $error,
    'errorArea' => $errorArea,
    'notice' => $notice,
    'noticeArea' => $noticeArea,
    'pagination' => [
        'current' => $pageNumber,
        'pages' => $paginationPages,
        'pageCount' => $pageCount,
        'total' => $totalUsers,
        'from' => $showingFrom,
        'to' => $showingTo,
        'previousUrl' => $pageNumber > 1
            ? $pageUrl($pageNumber - 1)
            : null,
        'nextUrl' => $pageNumber < $pageCount
            ? $pageUrl($pageNumber + 1)
            : null,
        'urls' => array_combine(
            $paginationPages,
            array_map($pageUrl, $paginationPages)
        ),
    ],
];
