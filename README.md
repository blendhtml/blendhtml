# Directory structure

```
Blendhtml/
├── Home/
│   └── index/
│       ├── controller.php
│       ├── layout.bhtml.twig
│       └── index.bhtml.twig
│
├── Auth/
│   ├── login/
│   │   ├── controller.php
│   │   ├── layout.bhtml.twig
│   │   └── index.bhtml.twig
│   │
│   └── register/
│       ├── controller.php
│       └── index.bhtml.twig
│
└── Blog/
    ├── index/
    │   └── index.bhtml.twig
    │
    └── post/
        ├── controller.php
        └── index.bhtml.twig
```

# URL mapping

```
/home
/auth/login
/auth/register
/blog/index
/blog/post
```

# Bootstrap

```php
require 'vendor/autoload.php';

use Blendhtml\Core\Application;

echo (
    new Application(
        __DIR__ . '/pages'
    )
)->render(
    $_SERVER['REQUEST_URI']
);
```
