<?php

declare(strict_types=1);

namespace Verdient\Task\Event;

use Verdient\Task\Payload;
use Verdient\Task\TaskInterface;

/**
 * 任务消费事件
 *
 * @author Verdient。
 */
class Consumed
{
    /**
     * @param TaskInterface $task 任务
     * @param Payload $payload 载荷
     * @param float $startAt 开始时间
     * @param float $endAt 结束时间
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly TaskInterface $task,
        public readonly Payload $payload,
        public readonly float $startAt,
        public readonly float $endAt
    ) {}
}
