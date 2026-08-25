# Installation guide

## Requirements

- PHP 8.3 or later.
- Yii2 22 development line.
- Composer.

## Install the Yii2 adapter

While version `0.3` is under development, run:

```bash
composer require yii2-extensions/inertia:^0.3@dev
```

The adapter requires [`php-forge/inertia`](https://github.com/php-forge/inertia), so Composer installs the
framework-agnostic protocol core automatically.

## Register the integration

```php
// config/web.php
return [
    'bootstrap' => [\yii\inertia\Bootstrap::class],
];
```

The bootstrap class:

- registers the `@inertia` alias;
- registers `yii\inertia\Manager` as the `inertia` component when the application has not configured one;
- normalizes outgoing Yii redirects immediately before a response is sent.

## Install the JavaScript client

Install the official Inertia client package for the application's frontend framework. For example:

```bash
npm install @inertiajs/react react react-dom
```

or:

```bash
npm install @inertiajs/vue3 vue
```

The PHP adapter does not select or bootstrap a JavaScript framework.

## Add Vite when required

Install [`php-forge/vite`](https://github.com/php-forge/vite) separately when the application needs a Vite manifest or
development-server integration:

```bash
composer require php-forge/vite:^0.1@dev
```

This dependency is optional because the Inertia protocol does not require Vite.

## Next steps

- ⚙️ [Configuration reference](configuration.md)
- 💡 [Usage examples](examples.md)
- 🧪 [Testing guide](testing.md)
