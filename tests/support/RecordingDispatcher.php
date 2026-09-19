<?php

declare(strict_types=1);

namespace yii\inertia\tests\support;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * PSR-14 dispatcher that records every dispatched event for assertions.
 */
final class RecordingDispatcher implements EventDispatcherInterface
{
    /**
     * @var list<object> Events in dispatch order.
     */
    public array $events = [];

    public function dispatch(object $event): object
    {
        $this->events[] = $event;

        return $event;
    }
}
