#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__, 5) . '/vendor/autoload.php';

use Blendhtml\Core\Auth\AuthAdmin;

# AuthAdmin::setOtpLength(6);
# AuthAdmin::removeOtpLength();

# AuthAdmin::setOtpLifetime(600);
# AuthAdmin::removeOtpLifetime();

# AuthAdmin::setOtpMaxAttempts(5);
# AuthAdmin::removeOtpMaxAttempts();

# AuthAdmin::setSessionLifetime(1209600);
# AuthAdmin::removeSessionLifetime();

# AuthAdmin::setRequestCodeEmailRateLimit(5, 900);
# AuthAdmin::removeRequestCodeEmailRateLimit();

# AuthAdmin::setRequestCodeIpRateLimit(20, 900);
# AuthAdmin::removeRequestCodeIpRateLimit();

# AuthAdmin::setVerifyCodeEmailRateLimit(10, 900);
# AuthAdmin::removeVerifyCodeEmailRateLimit();

# AuthAdmin::setVerifyCodeIpRateLimit(30, 900);
# AuthAdmin::removeVerifyCodeIpRateLimit();

# AuthAdmin::setEmailAccessMode('any');
# AuthAdmin::setEmailAccessMode('whitelist');
# AuthAdmin::removeEmailAccessMode();

# AuthAdmin::addEmailToWhitelist('it-consulting@nikolajev.ee');
# AuthAdmin::removeEmailFromWhitelist('it-consulting@nikolajev.ee');

# AuthAdmin::setCookieName('blendhtml_auth');
# AuthAdmin::removeCookieName();

# AuthAdmin::setCookieSameSite('Lax');
# AuthAdmin::removeCookieSameSite();

# AuthAdmin::setTrustedProxies(['127.0.0.1']);
# AuthAdmin::removeTrustedProxies();