<?php

declare(strict_types=1);

namespace Verdient\Task\Event;

use Verdient\Task\TaskInterface;

/**
 * 工作进程创建事件
 *
 * @author Verdient。
 */
class WorkerCreated
{
    /**
     * @param TaskInterface $task 任务
     * @param int $pid 进程编号
     * @param int $current 当前进程数量
     * @param int $idle 空闲进程数量
     * @param int $max 最大进程数量
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly TaskInterface $task,
        public readonly int $pid,
        public readonly int $current,
        public readonly int $idle,
        public readonly int $max
    ) {}
}
