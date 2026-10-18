<?php

use Blendhtml\Core\Context;

$contents = json_decode(
        file_get_contents(__DIR__ . '/' . Context::locale() . '.json'),
        true
) ?: [];

$favicon = sprintf(
        'data:image/svg+xml,%s',
        rawurlencode(
                "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'>
            <text x='32' y='56'
                  text-anchor='middle'
                  font-family='Arial Black,Arial,sans-serif'
                  font-size='64'
                  font-weight='900'>"
                . htmlspecialchars($contents['favicon_letter'] ?? 'B')
                . "</text>
        </svg>"
        )
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($contents['title'] ?? '500') ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
          rel="stylesheet">

    <link rel="icon" type="image/svg+xml" href="<?= $favicon ?>">

    <?php echo file_get_contents(__DIR__ . '/styles.html'); ?>
</head>
<body>

<div class="aurora">
    <div class="blob"></div>
    <div class="blob"></div>
    <div class="blob"></div>
</div>

<div class="grid"></div>
<div class="noise"></div>

<div class="shape s1"></div>
<div class="shape s2"></div>
<div class="shape s3"></div>

<div class="cursor-glow"></div>

<div class="wrapper">
    <div class="card">

        <div class="badge">
            <?= htmlspecialchars($contents['badge'] ?? '') ?>
        </div>

        <div class="code">
            500
        </div>

        <h1 class="title">
            <?= htmlspecialchars($contents['headline'] ?? '') ?>
        </h1>

        <p class="description">
            <?= nl2br(htmlspecialchars($contents['description'] ?? '')) ?>
        </p>

        <div class="protocol">
            <?= htmlspecialchars($contents['protocol'] ?? '') ?>
            
            <?php if (!empty($errorId)): ?>
                <div class="error-id">
                    <code><?= htmlspecialchars($errorId) ?></code>
                </div>
            <?php endif; ?>
        </div>

        <div class="status">
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
        </div>

        <div class="actions">

            <a href="/" class="btn primary">
                <?= htmlspecialchars($contents['homeButton'] ?? '') ?>
            </a>

            <a href="javascript:location.reload()" class="btn secondary">
                <?= htmlspecialchars($contents['retryButton'] ?? '') ?>
            </a>

        </div>

    </div>
</div>

<script>
    const glow = document.querySelector('.cursor-glow');

    window.addEventListener('mousemove', (e) => {
        glow.style.left = e.clientX + 'px';
        glow.style.top = e.clientY + 'px';
    });
</script>

</body>
</html>