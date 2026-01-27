<?php

declare(strict_types=1);

namespace Verdient\Task\Process;

/**
 * 父进程侧进程接口
 *
 * @author Verdient。
 */
interface ParentSideProcessInterface extends ProcessInterface
{
    /**
     * 设置为守护进程
     *
     * @param bool $value 是否守护进程
     *
     * @author Verdient。
     */
    public function daemonize(bool $daemonize = true): static;

    /**
     * 保留文件描述符
     *
     * @author Verdient。
     */
    public function keepFileDescriptors(): static;

    /**
     * 启动进程
     *
     * @author Verdient。
     */
    public function start(): void;

    /**
     * 获取进程是否正在运行
     *
     * @author Verdient。
     */
    public function isRunning(): bool;

    /**
     * 获取处理程序
     *
     * @author Verdient。
     */
    public function getHandler(): HandlerInterface;
}
