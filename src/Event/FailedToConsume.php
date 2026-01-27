<?php

declare(strict_types=1);

namespace Verdient\Task\Event;

use Throwable;
use Verdient\Task\Payload;
use Verdient\Task\TaskInterface;

/**
 * 任务消费失败事件
 *
 * @author Verdient。
 */
class FailedToConsume
{
    /**
     * @param TaskInterface $task 任务
     * @param Payload $payload 载荷
     * @param Throwable $throwable 异常对象
     * @param float $startAt 开始时间
     * @param float $endAt 结束时间
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly TaskInterface $task,
        public readonly Payload $payload,
        public readonly Throwable $throwable,
        public readonly float $startAt,
        public readonly float $endAt
    ) {}
}
