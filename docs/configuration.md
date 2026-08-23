# Configuration reference

## Overview

`yii2-extensions/inertia` registers a Yii2 application component that translates Yii request and application state
into `php-forge/inertia` inputs, then maps protocol results to Yii responses.

## Basic configuration

```php
// config/web.php
use yii\inertia\Manager;

return [
    'bootstrap' => [\yii\inertia\Bootstrap::class],
    'components' => [
        'inertia' => [
            'class' => Manager::class,
            'id' => 'app',
            'rootView' => '@app/views/layouts/inertia.php',
            'version' => static function (): string {
                $path = dirname(__DIR__) . '/public/build/manifest.json';

                return is_file($path) ? (string) filemtime($path) : '';
            },
            'shared' => [
                'auth.user' => static fn(): array|null => Yii::$app->user->isGuest
                    ? null
                    : ['id' => Yii::$app->user->getId()],
                'app.name' => static fn(): string => Yii::$app->name,
            ],
        ],
    ],
];
```

## Manager properties

### `id`

DOM ID used by the default root view. The default is `app`.

### `rootView`

View file rendered for the initial full HTML visit. The default is `@inertia/views/app.php`.

The view receives:

- `id`: the configured root element ID;
- `page`: a `PHPForge\Inertia\Page` instance;
- `pageJson`: HTML-safe serialized page data;
- values passed through the third argument of `Inertia::render()`.

### `version`

Asset version compared with `X-Inertia-Version`. It accepts a string, integer, zero-argument closure, or closure that
accepts the current `yii\web\Request`.

### `shared`

Props included in every rendered page. Dot notation creates nested values. Page-specific props take precedence after
the core resolves shared props.

### `errorFlashKey`

Session flash key mapped to `props.errors`. The default is `errors`. Its value must be an array whose string keys map
to a string or a list of strings.

### `encryptHistory`, `clearHistory`, and `preserveFragment`

Boolean page options passed directly to the protocol core. Each option defaults to `false`.

### `exposeSharedProps`

Controls whether the core includes shared-prop metadata in the page. The default is `true`.

### `protocol`

Optional `PHPForge\Inertia\Protocol` instance. Applications normally use the default instance; injection is useful
when a custom core clock is required for deterministic tests.

## Root view

A custom root view should type its page with the neutral core class:

```php
<?php

use PHPForge\Inertia\Page;

/**
 * @var string $id
 * @var Page $page
 * @var string $pageJson
 */
?>
<div id="<?= $id ?>" data-page="<?= htmlspecialchars($pageJson, ENT_QUOTES, 'UTF-8') ?>"></div>
```

Use the supplied `pageJson` value instead of serializing the page again.

## CSRF protection

`yii\inertia\web\Request` implements the cookie-to-header flow used by the Inertia client:

```php
return [
    'components' => [
        'request' => [
            'class' => \yii\inertia\web\Request::class,
            'cookieValidationKey' => 'your-secret-key',
        ],
    ],
];
```

The component publishes a non-HTTP-only `XSRF-TOKEN` cookie. The client returns it in `X-XSRF-TOKEN`, and the request
component validates and unmasks it for Yii's CSRF validation.

## Redirect normalization

For Inertia requests, the bootstrap class routes redirect responses through the protocol core. This includes method
redirect normalization and fragment-only redirect handling. Existing `Vary` values are preserved and merged with
`X-Inertia`.

## Next steps

- 📚 [Installation guide](installation.md)
- 💡 [Usage examples](examples.md)
- 🧪 [Testing guide](testing.md)
