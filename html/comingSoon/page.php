<?php

use Blendhtml\Core\Context;

$contents = json_decode(
    file_get_contents(__DIR__ . '/' . Context::locale(). '.json'),
    true
) ?: [];

$url = getenv('COMING_SOON_URL')
    ?: ($contents['footer']['url'] ?? 'blendhtml.com');

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

    <title><?= htmlspecialchars($contents['title'] ?? 'Coming Soon') ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

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
<div class="glow"></div>

<div class="wrapper">
    <div class="card">

        <div class="badge">
            <?= htmlspecialchars($contents['badge'] ?? '') ?>
        </div>

        <div class="icon">
            <?= htmlspecialchars($contents['icon'] ?? '') ?>
        </div>

        <h1>
            <?= htmlspecialchars($contents['headline'] ?? '') ?>
        </h1>

        <p class="subtitle">
            <?= nl2br(htmlspecialchars($contents['subtitle'] ?? '')) ?>
        </p>

        <div class="status">
            <div class="dot"></div>
            <span>
                <?= htmlspecialchars($contents['status']['text'] ?? '') ?>
            </span>
        </div>

        <div class="footer">
            <?= htmlspecialchars($contents['footer']['text'] ?? '') ?>

            <a href="https://<?= htmlspecialchars($url) ?>">
                <?= htmlspecialchars($url) ?>
            </a>
        </div>

    </div>
</div>

<script>
    const glow = document.querySelector('.glow');

    window.addEventListener('mousemove', (e) => {
        glow.style.left = e.clientX + 'px';
        glow.style.top = e.clientY + 'px';
    });
</script>

</body>
</html>
