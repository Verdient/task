<?php

declare(strict_types=1);

namespace Verdient\Task\ProcessDriver;

use Override;
use Verdient\Task\Process\ChildrenProcess;
use Verdient\Task\Process\HandlerInterface;

/**
 * ProcOpen工作进程启动处理程序
 *
 * @author Verdient。
 */
class ProcOpenBootHandler implements HandlerInterface
{
    /**
     * @param int $fd 文件描述符
     *
     * @author Verdient。
     */
    public function __construct(protected int $fd) {}

    /**
     * @author Verdient。
     */
    #[Override]
    public function handle(ChildrenProcess $process): void
    {
        $process->keepedFileDescriptorNames([readlink('/proc/self/fd/' . $this->fd)]);
    }
}
