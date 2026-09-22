<?php

declare(strict_types=1);

use Blendhtml\Core\VisitMetricsV1\Entity\VisitMetricsConsentV1;
use Blendhtml\Core\VisitMetricsV1\Entity\VisitMetricsV1;
use Blendhtml\Core\Auth\Auth;
use Blendhtml\Doctrine\Doctrine;


Auth::guard();

Auth::requireRole('admin');

$em = Doctrine::em();

$activities = $em
    ->getRepository(VisitMetricsV1::class)
    ->findBy([], ['timestamp' => 'DESC']);

$consents = $em
    ->getRepository(VisitMetricsConsentV1::class)
    ->findBy([], ['timestamp' => 'DESC']);

return [
    'activities' => $activities,
    'consents' => $consents,
];