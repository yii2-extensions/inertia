# Installation guide

## Requirements

- PHP 8.3 or later, with the extensions required by Composer's resolved dependencies.
- `yiisoft/yii2` `^22.0@dev`. Yii 2.0.x, including 2.0.55, is not declared compatible.
- Composer. The adapter installs `php-forge/inertia` `^0.5` and `psr/event-dispatcher` `^1.0` automatically.
- An application-owned Inertia JavaScript client and build for interactive pages.

These requirements come from `composer.json`. Match the installed adapter and core revisions when consulting their APIs.
The application lockfile records the exact Yii development revision.

## Install the adapter

In an existing Yii 22 application:

```bash
composer require yii2-extensions/inertia:^0.6
composer check-platform-reqs
```

For an application whose root requirements do not yet allow the Yii development line, declare it explicitly:

```bash
composer require yiisoft/yii2:^22.0@dev yii2-extensions/inertia:^0.6
```

Review Composer's proposed dependency changes if the application currently requires Yii 2.0.x. Changing a constraint
alone does not migrate that application. There is no need to lower the application's global `minimum-stability`.
Authorize `yiisoft/yii2-composer` in the application only if its installation needs that Composer plugin; the adapter's
own `allow-plugins` setting does not propagate to consumers.

## Register the integration

Merge the following entries into the existing web configuration, preserving its bootstrap entries and components:

```php
use yii\inertia\Bootstrap;
use yii\inertia\web\Request;

return [
    'bootstrap' => [Bootstrap::class],
    'components' => [
        'request' => [
            'class' => Request::class,
            'cookieValidationKey' => getenv('COOKIE_VALIDATION_KEY')
                ?: throw new RuntimeException('Set COOKIE_VALIDATION_KEY in the application environment.'),
            'parsers' => [
                'application/json' => \yii\web\JsonParser::class,
            ],
        ],
    ],
];
```

Use the application's existing secret management for a stable, private cookie validation key. Keep its request settings,
access rules, and CSRF validation. If the application already uses a custom request subclass, reconcile its behavior
with this adapter rather than silently replacing it. Keep existing body parsers when adding the JSON parser used for
Inertia form submissions.

The bootstrap registers the `@inertia` alias and creates an `inertia` component of type `yii\inertia\Manager` when one
is not configured. For requests carrying the Inertia header, it attaches response normalization before send. Keep the
application's session component enabled if it uses validation errors or flash messages.

The default root view renders the page payload. For the cookie-to-header flow, initialize the token in an
application-owned root view with `Yii::$app->getRequest()->getCsrfToken()` before the first write request. For this view
and an asset version, continue with [configuration](configuration.md) and the
[application root-view recipe](examples.md#application-root-view).

## Connect the frontend

Use the official [Inertia client setup](https://inertiajs.com/docs/v3/installation/client-side-setup) for the
application's framework and client version. Configure its resolver to find the exact component name passed by PHP, such
as `Dashboard` or `Users/Index`, and match the root element ID (`app` by default). The bundled view uses the Inertia v3
JSON-script format. For an older client, check its initial-page format and advanced protocol support; see
[root views](configuration.md#root-view) and [response headers](configuration.md#redirects-and-response-headers).

This adapter has no Vite component, asset bundle, JavaScript package manifest, SSR server, or frontend installer.
Register the application's own compiled bundle or existing asset integration in the root view. Installing the PHP
package does not build assets or mount a client. Check client support for each advanced prop feature in use.

## Verify the integration

- Request a page without `X-Inertia`: expect HTML with one root element and its JSON page payload.
- Visit through the configured client: expect JSON with `X-Inertia: true`, a component name, props, URL, and version.
- Confirm the component resolves, a second visit navigates, and a valid form submission passes CSRF validation.
- After changing the configured asset version, check that an outdated Inertia GET receives a `409` location response.

Validate the integration with the consuming application's existing server checks and frontend flow.

---

[Configuration →](configuration.md) · [Back to documentation](../README.md#documentation)
