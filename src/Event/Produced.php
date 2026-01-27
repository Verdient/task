<?php

declare(strict_types=1);

namespace Verdient\Task\Event;

use Verdient\Task\TaskInterface;

/**
 * 任务生产事件
 *
 * @author Verdient。
 */
class Produced
{
    /**
     * @param TaskInterface $task 任务
     * @param ?array $data 数据
     * @param float $startAt 开始时间
     * @param float $endAt 结束时间
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly TaskInterface $task,
        public readonly ?array $data,
        public readonly float $startAt,
        public readonly float $endAt
    ) {}
}
