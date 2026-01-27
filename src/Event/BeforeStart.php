<?php

declare(strict_types=1);

namespace Verdient\Task\Event;

use Verdient\Task\TaskInterface;

/**
 * 启动前事件
 *
 * @author Verdient。
 */
class BeforeStart
{
    /**
     * @param TaskInterface $task 任务
     *
     * @author Verdient。
     */
    public function __construct(public readonly TaskInterface $task) {}
}
