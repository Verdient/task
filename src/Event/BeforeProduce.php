<?php

declare(strict_types=1);

namespace Verdient\Task\Event;

use Verdient\Task\TaskInterface;

/**
 * 任务生产开始事件
 *
 * @author Verdient。
 */
class BeforeProduce
{
    /**
     * @param TaskInterface $task 任务
     *
     * @author Verdient。
     */
    public function __construct(public readonly TaskInterface $task) {}
}
