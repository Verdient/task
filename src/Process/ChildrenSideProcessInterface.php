<?php

declare(strict_types=1);

namespace Verdient\Task\Process;

/**
 * 子进程侧进程接口
 *
 * @author Verdient。
 */
interface ChildrenSideProcessInterface extends ProcessInterface
{
    /**
     * 使当前进程蜕变为一个守护进程
     *
     * @author Verdient。
     */
    public function daemonize(): void;

    /**
     * 保留的文件描述符名称
     *
     * @param array $names 文件描述符名称集合
     *
     * @author Verdient。
     */
    public function keepedFileDescriptorNames(array $names);
}
