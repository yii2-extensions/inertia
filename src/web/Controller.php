<?php

declare(strict_types=1);

namespace yii\inertia\web;

use yii\inertia\Inertia;
use yii\web\Response;

/**
 * Base controller for Inertia-driven pages.
 */
abstract class Controller extends \yii\web\Controller
{
    /**
     * Renders an Inertia page.
     *
     * @param string $component Inertia component name.
     * @param array<string, mixed> $props Props to pass to the component.
     * @param array<string, mixed> $viewData Additional view data.
     *
     * @return Response Response containing the rendered Inertia page.
     */
    protected function inertia(string $component, array $props = [], array $viewData = []): Response
    {
        return Inertia::render($component, $props, $viewData);
    }

    /**
     * Returns an Inertia location response.
     *
     * @param array<array-key, mixed>|string $url URL to redirect to, either as a string or an array that can be
     * processed by `Url::to()`.
     *
     * @return Response Response containing the Inertia location header.
     */
    protected function location(array|string $url): Response
    {
        return Inertia::location($url);
    }
}
