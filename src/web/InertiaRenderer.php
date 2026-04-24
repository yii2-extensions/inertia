<?php

declare(strict_types=1);

namespace yii\inertia\web;

use yii\inertia\Inertia;
use yii\web\Response;

/**
 * Renders an Inertia page response from a component identifier.
 *
 * Implements {@see ResponseRendererInterface} by delegating to the `inertia` application component, so controllers
 * receive a JSON page payload for Inertia requests or the initial HTML shell for standard browser requests.
 *
 * Usage example:
 *
 * ```php
 * $renderer = Yii::$container->get(\yii\inertia\web\ResponseRendererInterface::class);
 *
 * return $renderer->render('Dashboard', ['user' => $user->toArray()]);
 * ```
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1.1
 */
final class InertiaRenderer implements ResponseRendererInterface
{
    public function render(string $component, array $props = [], array $viewData = []): Response
    {
        return Inertia::render($component, $props, $viewData);
    }
}
