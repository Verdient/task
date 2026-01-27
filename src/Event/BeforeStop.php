<?php

declare(strict_types=1);

namespace Verdient\Task\Event;

use Verdient\Task\TaskInterface;

/**
 * 关闭前事件
 *
 * @author Verdient。
 */
class BeforeStop
{
    /**
     * @param TaskInterface $task 任务
     *
     * @author Verdient。
     */
    public function __construct(public readonly TaskInterface $task) {}
}
