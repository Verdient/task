<?php

declare(strict_types=1);

namespace Verdient\Task\ProcessDriver;

use Verdient\Task\Process\HandlerInterface;
use Verdient\Task\Socket;

/**
 * 进程驱动接口
 *
 * @author Verdient。
 */
interface ProcessDriverInterface
{
    /**
     * 启动进程
     *
     * @param HandlerInterface $handler 处理函数
     *
     * @author Verdient。
     */
    public function start(HandlerInterface $handler): void;

    /**
     * 获取流
     *
     * @author Verdient。
     */
    public function getSocket(): ?Socket;

    /**
     * 使当前进程蜕变为一个守护进程
     *
     * @param bool $noChdir 不改变当前目录
     * @param bool $noClose 不关闭文件描述符
     *
     * @author Verdient。
     */
    public function daemonize(bool $noChdir = true, bool $noClose = true): void;

    /**
     * 获取进程启动处理程序
     *
     * @author Verdient。
     */
    public function bootHandler(): ?HandlerInterface;
}
