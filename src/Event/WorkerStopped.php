<?php

declare(strict_types=1);

namespace Verdient\Task\Event;

use Verdient\Task\Process\ChildrenProcess;
use Verdient\Task\TaskInterface;

/**
 * 工作进程停止事件
 *
 * @author Verdient。
 */
class WorkerStopped
{
    /**
     * @param TaskInterface $task 任务
     * @param ChildrenProcess $process 进程
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly TaskInterface $task,
        public readonly ChildrenProcess $process
    ) {}
}
