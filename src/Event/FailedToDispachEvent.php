<?php

declare(strict_types=1);

namespace Verdient\Task\Event;

use Throwable;
use Verdient\Task\TaskInterface;

/**
 * 事件派发失败
 *
 * @author Verdient。
 */
class FailedToDispachEvent
{
    /**
     * @param TaskInterface $task 任务
     * @param Throwable $throwable 异常对象
     * @param object $event 事件对象
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly TaskInterface $task,
        public readonly Throwable $throwable,
        public readonly object $event
    ) {}
}
