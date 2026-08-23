# Usage examples

## Shared props

```php
use yii\inertia\Inertia;

Inertia::share(
    [
        'auth.user' => static fn(): array|null => Yii::$app->user->isGuest
            ? null
            : ['id' => Yii::$app->user->getId()],
    ],
);
```

## Rendering a page

```php
return Inertia::render(
    'Users/Index',
    [
        'users' => $dataProvider->getModels(),
        'filters' => Yii::$app->request->getQueryParams(),
    ],
);
```

## Validation redirect

```php
if (!$model->validate()) {
    Yii::$app->session->setFlash('errors', $model->getErrors());

    return $this->redirect(['create']);
}

Yii::$app->session->setFlash('success', 'User saved.');

return $this->redirect(['view', 'id' => $model->id]);
```

## External location visit

```php
return Inertia::location('https://example.com/account/login');
```

The adapter returns a standard redirect for a normal request and an Inertia location response for an Inertia request.

## Deferred props

Deferred props are excluded from the initial response and loaded after the page renders. Props with the same group
are fetched together.

```php
return Inertia::render(
    'Dashboard',
    [
        'stats' => $stats,
        'users' => Inertia::defer(static fn(): array => User::find()->asArray()->all()),
        'roles' => Inertia::defer(static fn(): array => Role::find()->asArray()->all(), 'sidebar'),
    ],
);
```

Pass `true` as the third argument to rescue a failed deferred callback according to the core protocol behavior.

## Optional props

Optional props are resolved only when a partial reload explicitly requests them.

```php
return Inertia::render(
    'Users/Show',
    [
        'user' => $user->toArray(),
        'activity' => Inertia::optional(static fn(): array => $activity),
    ],
);
```

## Always props

Always props remain included during partial reloads even when the request does not list them.

```php
return Inertia::render(
    'Dashboard',
    [
        'auth' => Inertia::always(['user' => Yii::$app->user->identity]),
        'stats' => $stats,
    ],
);
```

## Merge props

```php
return Inertia::render(
    'Users/Index',
    [
        'users' => Inertia::merge($paginatedUsers)->append('data', 'id'),
        'logs' => Inertia::deepMerge($nestedLogs),
        'messages' => Inertia::merge($messages)->prepend('data'),
    ],
);
```

## Once props

```php
return Inertia::render(
    'Settings',
    [
        'countries' => Inertia::once(static fn(): array => $countries)
            ->as('countries-v1')
            ->until(3600),
    ],
);
```

## Core prop API

The facade returns `PHPForge\Inertia\Prop` objects. Advanced operations, including scroll props and merge metadata,
therefore follow the [`php-forge/inertia` API](https://github.com/php-forge/inertia).

Prop callbacks take no framework argument. Capture application state explicitly or read it from `Yii::$app` inside
the closure.

## Next steps

- 📚 [Installation guide](installation.md)
- ⚙️ [Configuration reference](configuration.md)
- 🧪 [Testing guide](testing.md)
