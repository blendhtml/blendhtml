<?php

declare(strict_types=1);

namespace Blendhtml\Core;

final class Adapter
{
    public static function emailSender(): object
    {
        $projectClass = 'Blendhtml\\Adapter\\EmailSender';

        if (class_exists($projectClass)) {
            return new $projectClass();
        }

        return new \Blendhtml\CoreAdapter\EmailSender();
    }

    public static function authMail(): object
    {
        $projectClass = 'Blendhtml\\Adapter\\Auth\\AuthMail';

        if (class_exists($projectClass)) {
            return new $projectClass();
        }

        return new \Blendhtml\CoreAdapter\Auth\AuthMail();
    }
}