<!-- markdownlint-disable MD041 -->
<p align="center">
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://www.yiiframework.com/image/design/logo/yii3_full_for_dark.svg">
        <source media="(prefers-color-scheme: light)" srcset="https://www.yiiframework.com/image/design/logo/yii3_full_for_light.svg">
        <img src="https://www.yiiframework.com/image/design/logo/yii3_full_for_light.svg" alt="Yii Framework" width="80%">
    </picture>
    <h1 align="center">Inertia for Yii2</h1>
    <br>
</p>
<!-- markdownlint-enable MD041 -->

<p align="center">
    <a href="https://github.com/yii2-extensions/inertia/actions/workflows/build.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/yii2-extensions/inertia/build.yml?style=for-the-badge&logo=github&label=PHPUnit" alt="PHPUnit">
    </a>
    <a href="https://dashboard.stryker-mutator.io/reports/github.com/yii2-extensions/inertia/main" target="_blank">
        <img src="https://img.shields.io/endpoint?style=for-the-badge&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyii2-extensions%2Finertia%2Fmain" alt="Mutation Testing">
    </a>
    <a href="https://github.com/yii2-extensions/inertia/actions/workflows/static.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/yii2-extensions/inertia/static.yml?style=for-the-badge&logo=github&label=PHPStan" alt="PHPStan">
    </a>
    <a href="https://github.com/yii2-extensions/inertia/actions/workflows/security.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/yii2-extensions/inertia/security.yml?style=for-the-badge&label=Security&logo=github" alt="Security">
    </a>
</p>

<p align="center">
    <em>Connect Yii requests, responses, views, sessions, and redirects to the Inertia protocol</em>
</p>

## Architecture

The packages have deliberately separate responsibilities:

- [`php-forge/inertia`](https://github.com/php-forge/inertia) implements the framework-agnostic protocol, page model,
  prop resolution, headers, redirects, and result objects.
- `yii2-extensions/inertia` adapts Yii2 application state to that core and maps its results back to Yii responses.
- [`php-forge/vite`](https://github.com/php-forge/vite) provides optional, framework-agnostic Vite manifest and development
  server support.

This adapter does not contain Vite integration or framework-specific JavaScript client packages.

## Installation

While `0.2` is under development, install the adapter with:

```bash
composer require yii2-extensions/inertia:^0.2@dev
```

Register its bootstrap class:

```php
return [
    'bootstrap' => [\yii\inertia\Bootstrap::class],
];
```

The adapter installs `php-forge/inertia` as its protocol dependency.

## Quick start

```php
use yii\inertia\Inertia;
use yii\web\Controller;
use yii\web\Response;

final class SiteController extends Controller
{
    public function actionIndex(): Response
    {
        return Inertia::render(
            'Dashboard',
            ['stats' => ['visits' => 42]],
        );
    }
}
```

The convenience controller exposes the same operation as `$this->inertia()`:

```php
use yii\inertia\web\Controller;
use yii\web\Response;

final class SiteController extends Controller
{
    public function actionIndex(): Response
    {
        return $this->inertia('Dashboard', ['stats' => ['visits' => 42]]);
    }
}
```

## Configuration

```php
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
                'app.name' => static fn(): string => Yii::$app->name,
            ],
        ],
    ],
];
```

Version callbacks may accept the current `yii\web\Request`. Prop callbacks are framework-neutral zero-argument
closures and are resolved by `php-forge/inertia`.

## Prop factories

The `yii\inertia\Inertia` facade delegates prop creation directly to the core:

```php
return Inertia::render(
    'Dashboard',
    [
        'stats' => Inertia::always($stats),
        'users' => Inertia::defer(static fn(): array => User::find()->asArray()->all()),
        'activity' => Inertia::optional(static fn(): array => $activity),
        'items' => Inertia::merge($items)->append('data', 'id'),
        'countries' => Inertia::once(static fn(): array => $countries)->as('countries-v1'),
    ],
);
```

The facade also provides `deepMerge()` and `scroll()`. See the
[`php-forge/inertia` documentation](https://github.com/php-forge/inertia) for protocol and prop semantics.

## Validation and flash messages

The adapter reads the session flash key configured by `Manager::$errorFlashKey` and passes it to the core as
`props.errors`. Other flashes are emitted in the top-level `flash` page field. Flashes are consumed only after a page
result is created, so version conflicts and failed page creation preserve them.

```php
if (!$model->validate()) {
    Yii::$app->session->setFlash('errors', $model->getErrors());

    return $this->redirect(['create']);
}

Yii::$app->session->setFlash('success', 'Record created.');

return $this->redirect(['view', 'id' => $model->id]);
```

## CSRF protection

Use `yii\inertia\web\Request` for Inertia's cookie-to-header CSRF flow:

```php
'request' => [
    'class' => \yii\inertia\web\Request::class,
    'cookieValidationKey' => 'your-secret-key',
],
```

## Vite

Install and configure [`php-forge/vite`](https://github.com/php-forge/vite) when the application uses Vite. Asset
discovery and development-server behavior are intentionally independent of this Yii2 adapter.

## Documentation

- [Installation guide](docs/installation.md)
- [Configuration reference](docs/configuration.md)
- [Usage examples](docs/examples.md)
- [Testing guide](docs/testing.md)

## Package information

[![PHP](https://img.shields.io/badge/%3E%3D8.3-777BB4.svg?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/releases/8.3/en.php)
[![Yii 22.0.x](https://img.shields.io/badge/22.0.x-0073AA.svg?style=for-the-badge&logo=yii&logoColor=white)](https://github.com/yiisoft/yii2/tree/22.0)
[![Latest Stable Version](https://img.shields.io/packagist/v/yii2-extensions/inertia.svg?style=for-the-badge&logo=packagist&logoColor=white&label=Stable)](https://packagist.org/packages/yii2-extensions/inertia)
[![Total Downloads](https://img.shields.io/packagist/dt/yii2-extensions/inertia.svg?style=for-the-badge&logo=composer&logoColor=white&label=Downloads)](https://packagist.org/packages/yii2-extensions/inertia)

## Project status

[![Codecov](https://img.shields.io/codecov/c/github/yii2-extensions/inertia.svg?style=for-the-badge&logo=codecov&logoColor=white&label=Coverage)](https://codecov.io/github/yii2-extensions/inertia)
[![PHPStan Level Max](https://img.shields.io/badge/PHPStan-Level%20Max-4F5D95.svg?style=for-the-badge&logo=github&logoColor=white)](https://github.com/yii2-extensions/inertia/actions/workflows/static.yml)
[![Quality](https://img.shields.io/github/actions/workflow/status/yii2-extensions/inertia/quality.yml?style=for-the-badge&label=Quality&logo=github)](https://github.com/yii2-extensions/inertia/actions/workflows/quality.yml)
[![StyleCI](https://img.shields.io/badge/StyleCI-Passed-44CC11.svg?style=for-the-badge&logo=github&logoColor=white)](https://github.styleci.io/repos/1196150046?branch=main)

## Our social networks

[![Follow on X](https://img.shields.io/badge/-Follow%20on%20X-1DA1F2.svg?style=for-the-badge&logo=x&logoColor=white&labelColor=000000)](https://x.com/Terabytesoftw)
[![Follow on Facebook](https://img.shields.io/badge/-Follow%20on%20Facebook-1877F2.svg?style=for-the-badge&logo=facebook&logoColor=white&labelColor=000000)](https://www.facebook.com/wilmer.arambula.9)
[![Join our Subreddit](https://img.shields.io/badge/-Join%20our%20Subreddit-FF4500.svg?style=for-the-badge&logo=reddit&logoColor=white&labelColor=000000)](https://www.reddit.com/r/Yii2/)
[![Join on Telegram](https://img.shields.io/badge/-Join%20on%20Telegram-26A5E4.svg?style=for-the-badge&logo=telegram&logoColor=white&labelColor=000000)](https://t.me/yii_framework_in_english)

## License

[![License](https://img.shields.io/badge/License-BSD--3--Clause-brightgreen.svg?style=for-the-badge&logo=opensourceinitiative&logoColor=white&labelColor=555555)](LICENSE)
