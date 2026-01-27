<?php

declare(strict_types=1);

namespace Verdient\Task\Process;

use Verdient\Task\Process\ChildrenProcess;

/**
 * 处理程序接口
 *
 * @author Verdient。
 */
interface HandlerInterface
{
    /**
     * 处理任务
     *
     * @param ChildrenProcess $process 进程对象
     *
     * @author Verdient。
     */
    public function handle(ChildrenProcess $process): void;
}