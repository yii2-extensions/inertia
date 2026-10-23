# Usage examples

## Adapt the application recipes

Merge the [installation](installation.md#register-the-integration) and [manager configuration](configuration.md) into
the application's existing web configuration. Adapt the snippets below to its component resolver and asset build, and
keep a stable private cookie-validation key in its environment.

## Render from a controller action

Inside an existing controller with the application's access and verb rules:

```php
public function actionIndex(): \yii\web\Response
{
    return \yii\inertia\Inertia::render('Dashboard', ['stats' => ['visits' => 42]]);
}
```

The component string must match the frontend resolver. A controller extending `yii\inertia\web\Controller` can use
`$this->inertia('Dashboard', $props)` instead. This returns Yii's response, not a view string.

## Application root view

Create the application-owned file configured by `Manager::$rootView`, for example `@app/views/layouts/inertia.php`.
Register the application's own asset bundle where indicated and preserve the root ID, safe JSON script, CSRF
initialization, and Yii page lifecycle:

```php
<?php

declare(strict_types=1);

use PHPForge\Inertia\Page;
use yii\helpers\Html;

/**
 * @var string $id
 * @var Page $page
 * @var string $pageJson
 */

// Initialize the cookie used by the client's first state-changing request.
Yii::$app->getRequest()->getCsrfToken();

// Register the application's own asset bundle here before rendering the document.
$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Html::encode(Yii::$app->language) ?>">
    <head>
        <meta charset="<?= Html::encode(Yii::$app->charset) ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title data-inertia><?= Html::encode(Yii::$app->name) ?></title>
        <?php $this->head(); ?>
    </head>
    <body>
        <?php $this->beginBody(); ?>
        <div id="<?= Html::encode($id) ?>">
            <script type="application/json" data-page="<?= Html::encode($id) ?>"><?= $pageJson ?></script>
        </div>
        <?php $this->endBody(); ?>
    </body>
</html>
<?php $this->endPage();
```

The JSON script's `data-page` identifies the root for Inertia v3. For clients that read JSON from a root attribute, use
the encoding described in [configuration](configuration.md#root-view). Connect the application's client using
[installation](installation.md#connect-the-frontend).

## Shared props

After bootstrap, add a value for subsequent page renders in the current request:

```php
\yii\inertia\Inertia::share('auth.user', Yii::$app->user->isGuest
    ? null
    : ['id' => Yii::$app->user->getId()]);
```

The application must have a configured `user` component for this recipe. Select only the identity fields needed by the
client. For application-wide shared values, use the manager configuration. A page's `auth` prop can replace the shared
`auth` array; preserve needed nested values explicitly.

## Prop recipes

The following call uses synthetic, serializable arrays and zero-argument callbacks:

```php
declare(strict_types=1);

use yii\inertia\Inertia;

return Inertia::render('Dashboard', [
    'stats' => ['visits' => 42],
    'navigation' => Inertia::always(['dashboard' => '/dashboard']),
    'users' => Inertia::defer(static fn(): array => [['id' => 1, 'name' => 'Ada']]),
    'activity' => Inertia::optional(static fn(): array => ['Signed in']),
    'items' => Inertia::merge(['data' => [['id' => 1]]])->append('data', 'id'),
    'settings' => Inertia::deepMerge(['display' => ['compact' => true]]),
    'messages' => Inertia::merge(['data' => [['id' => 2]]])->prepend('data'),
    'countries' => Inertia::once(static fn(): array => ['GB', 'ES'])->as('countries-v1')->until(3600),
    'feed' => Inertia::scroll(
        ['data' => [['id' => 1]], 'page' => 1],
        Inertia::scrollMetadata(pageName: 'page', previousPage: null, nextPage: 2, currentPage: 1),
    ),
]);
```

| Factory                               | When to use it                                           | Example behavior                                                       |
| ------------------------------------- | -------------------------------------------------------- | ---------------------------------------------------------------------- |
| `always($value)`                      | A prop must survive partial filtering.                   | `navigation` remains when only `users` is requested.                   |
| `defer($callback, $group, $rescue)`   | Fetch a value after the initial response.                | `users` is omitted initially and listed in the default deferred group. |
| `optional($callback)`                 | Include only when a matching partial reload asks for it. | `activity` is absent initially.                                        |
| `merge($value)`                       | Supply client merge metadata.                            | `append('data', 'id')` names the wrapper and match key.                |
| `deepMerge($value)`                   | Request a recursive client merge.                        | Nested settings carry deep-merge metadata.                             |
| `once($callback)`                     | Let the client retain a value.                           | Countries use the `countries-v1` key and a relative expiration.        |
| `scroll($value, $metadata, $wrapper)` | Supply an infinite-scroll page.                          | The feed has current/adjacent cursors and a `data` merge path.         |
| `scrollMetadata(...)`                 | Build metadata consumed by `scroll()`.                   | Returns `ScrollMetadata`, rather than a prop wrapper.                  |

All prop callbacks take zero arguments. Deferred groups are fetched together by a compatible client; the optional third
`defer()` argument enables core rescue metadata for callback failures. Check client support before adding advanced
props. For fluent modifiers and exact contracts use the installed `php-forge/inertia` API.

A matching partial request to the page above uses:

```http
X-Inertia: true
X-Inertia-Partial-Component: Dashboard
X-Inertia-Partial-Data: users,activity
```

With these headers, `users` and `activity` resolve, `stats` is omitted, and always props survive. The partial component
name must match the rendered component; the headers are protocol input, not an authorization boundary.

## Validation redirect

Given a loaded application model and an authorized write action, validate before persistence:

```php
if (!$model->validate()) {
    Yii::$app->session->setFlash('errors', $model->getErrors());

    return $this->redirect(['create']);
}
```

Continue with the application's persistence logic, and set a success flash only after the write succeeds. The error
flash becomes `props.errors`; other flashes become the top-level `flash` field. `ManagerTest` checks those shapes,
consumption, rejection of a malformed error flash, and preservation across an asset-conflict reload.

## Redirects and external locations

Return `$this->redirect(['index'])` from the application's write action. Bootstrap normalizes the outgoing response for
Inertia requests. `BootstrapTest` triggers the before-send event for PUT, PATCH, and DELETE and checks that `302`
becomes `303`.

For a trusted external destination:

```php
return \yii\inertia\Inertia::location('https://example.com/account/login');
```

Expect `409` plus `X-Inertia-Location` for an Inertia request and a standard redirect for a normal request.

## Observe a resolved page

`ManagerTest` configures `PHPForge\Inertia\ResolvedPageObserver` with a callback taking the resolved payload and
shared-prop keys and checks initial and JSON responses. For application integration, set `pageObserver` before rendering
and select the diagnostics to record. Observer errors propagate; they preserve session flashes. A version conflict does
not notify a page observer. Protocol events are a separate integration through `eventDispatcher`; see
[configuration](configuration.md#observe-pages-and-protocol-results).

---

[Configuration](configuration.md) · [Migration →](migration.md) · [Back to documentation](../README.md#documentation)
