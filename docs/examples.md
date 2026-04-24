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

## CSRF protection

Configure the application `request` component to use `yii\inertia\web\Request`. This sets a non-`httpOnly`
`XSRF-TOKEN` cookie that Inertia's built-in HTTP client reads automatically and sends back as the `X-XSRF-TOKEN`
header on every request, mirroring Laravel's cookie-to-header pattern.

```php
// config/web.php
return [
    'components' => [
        'request' => [
            'class' => \yii\inertia\web\Request::class,
            'cookieValidationKey' => 'your-secret-key',
        ],
    ],
];
```

No client-side configuration is required — Inertia handles cookie reading and header injection automatically.

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

## External redirect for Inertia requests

```php
return Inertia::location('https://example.com/account/login');
```

## Deferred props

Props excluded from the initial response and loaded asynchronously after the page renders. Props sharing the same
group are fetched together.

```php
return Inertia::render(
    'Dashboard',
    [
        'stats' => $stats,
        'users' => Inertia::defer(fn () => User::find()->all()),
        'roles' => Inertia::defer(fn () => Role::find()->all(), 'sidebar'),
    ],
);
```

## Optional props

Props only resolved when the client explicitly requests them via a partial reload.

```php
return Inertia::render(
    'Users/Show',
    [
        'user' => $user->toArray(),
        'activity' => Inertia::optional(fn () => $user->getActivityLog()),
    ]
);
```

## Always props

Props included in every response, even during partial reloads that do not list them.

```php
return Inertia::render(
    'Dashboard',
    [
        'auth' => Inertia::always(fn () => ['user' => Yii::$app->user->identity]),
        'stats' => $stats,
    ],
);
```

## Merge props

Props that merge with existing client-side data during partial reloads instead of replacing it.

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

Props resolved once and cached on the client-side with an optional TTL.

```php
return Inertia::render(
    'Settings',
    [
        'countries' => Inertia::once(fn () => Country::find()->all())
            ->as('countries-v1')
            ->until(3600),
    ],
);
```

## Decoupling the presentation strategy

Controllers that inject `yii\inertia\web\ResponseRendererInterface` delegate the final output to whichever renderer is
registered in the DI container. `Bootstrap` binds the interface to `InertiaRenderer` by default, so no extra wiring is
needed for Inertia-only applications. Frontend overlays with a different presentation strategy (JSON, API) can override
the binding after bootstrap without modifying the action body.

```php
use yii\inertia\web\ResponseRendererInterface;
use yii\web\{Controller, Response};

final class UserController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly ResponseRendererInterface $renderer,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionShow(int $id): Response|string
    {
        $user = User::findOne($id);

        return $this->renderer->render('Users/Show', ['user' => $user->toArray()]);
    }
}
```

## Next steps

- 📚 [Installation Guide](installation.md)
- ⚙️ [Configuration Reference](configuration.md)
- 🧪 [Testing Guide](testing.md)
