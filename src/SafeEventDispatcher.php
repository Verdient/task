<?php

declare(strict_types=1);

namespace Verdient\Task;

use Override;
use Psr\EventDispatcher\EventDispatcherInterface;
use Verdient\Task\Event\FailedToDispachEvent;

/**
 * 安全事件调度器
 *
 * @author Verdient。
 */
class SafeEventDispatcher implements EventDispatcherInterface
{
    /**
     * @param EventDispatcherInterface $eventDispatcher 事件调度器
     * @param TaskInterface $task 任务
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly EventDispatcherInterface $eventDispatcher,
        protected TaskInterface $task
    ) {}

    /**
     * @author Verdient。
     */
    #[Override]
    public function dispatch(object $event)
    {
        try {
            return $this->eventDispatcher->dispatch($event);
        } catch (\Throwable $e) {
            try {
                $this->eventDispatcher->dispatch(new FailedToDispachEvent($this->task, $e, $event));
            } catch (\Throwable) {
            }
            return $event;
        }
    }
}
