<?php

declare(strict_types=1);

namespace yii\inertia;

use Closure;
use PHPForge\Inertia\Header;
use WeakReference;
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
     * @var (Closure(Event): void)|null Current application's class-level response handler.
     */
    private static Closure|null $responseNormalizer = null;

    /**
     * Registers the adapter and attaches response normalization for Inertia web requests.
     *
     * @param Application $app
     */
    public function bootstrap($app): void
    {
        if (self::$responseNormalizer !== null) {
            Event::off(Response::class, Response::EVENT_BEFORE_SEND, self::$responseNormalizer);
            self::$responseNormalizer = null;
        }

        Yii::setAlias('@inertia', __DIR__);

        if (!$app->has('inertia')) {
            $app->set('inertia', ['class' => Manager::class]);
        }

        if (
            !$app instanceof WebApplication
            || !$app->getRequest()->getHeaders()->has(Header::INERTIA->value)
        ) {
            return;
        }

        $application = WeakReference::create($app);

        self::$responseNormalizer = static function (Event $event) use ($application): void {
            $app = $application->get();
            $response = $event->sender;

            if (!$app instanceof WebApplication || Yii::$app !== $app || !$response instanceof Response) {
                return;
            }

            $manager = $app->get('inertia');

            if ($manager instanceof Manager) {
                $manager->normalizeResponse($response);
            }
        };

        Event::on(Response::class, Response::EVENT_BEFORE_SEND, self::$responseNormalizer);
    }
}
