<?php

declare(strict_types=1);

namespace yii\inertia;

use PHPForge\Inertia\RequestContext;
use Yii;
use yii\base\Application;
use yii\base\{BootstrapInterface, Event};
use yii\web\{Application as WebApplication, Response};

/**
 * Bootstraps response normalization for Yii2 requests carrying the Inertia protocol marker.
 */
final class Bootstrap implements BootstrapInterface
{
    /**
     * @param Application $app
     */
    public function bootstrap($app): void
    {
        Yii::setAlias('@inertia', __DIR__);

        if (!$app->has('inertia')) {
            $app->set('inertia', ['class' => Manager::class]);
        }

        if (
            !$app instanceof WebApplication
            || !$app->getRequest()->getHeaders()->has(RequestContext::HEADER_INERTIA)
        ) {
            return;
        }

        $app->getResponse()->on(
            Response::EVENT_BEFORE_SEND,
            static function (Event $event): void {
                $response = $event->sender;

                if (!$response instanceof Response) {
                    return;
                }

                $manager = Yii::$app->get('inertia');

                if ($manager instanceof Manager) {
                    $manager->normalizeResponse($response);
                }
            },
        );
    }
}
