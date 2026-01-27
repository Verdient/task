<?php

declare(strict_types=1);

namespace Verdient\Task\Event;

use Verdient\Task\Payload;
use Verdient\Task\TaskInterface;

/**
 * 任务消费开始事件
 *
 * @author Verdient。
 */
class BeforeConsume
{
    /**
     * @param TaskInterface $task 任务
     * @param Payload $payload 载荷
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly TaskInterface $task,
        public readonly Payload $payload
    ) {}
}
