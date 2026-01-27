<?php

declare(strict_types=1);

namespace Verdient\Task\Process;

use Closure;
use Verdient\Task\Socket;

/**
 * 进程接口
 *
 * @author Verdient。
 */
interface ProcessInterface
{
    /**
     * 设置进程名称
     *
     * @param string $name 进程名称
     *
     * @author Verdient。
     */
    public function name(string $name): static;

    /**
     * 清空文件描述符
     *
     * @author Verdient。
     */
    public function clearFileDescriptors(): static;

    /**
     * 设置阻塞
     *
     * @param bool $blocking 是否阻塞
     *
     * @author Verdient。
     */
    public function blocking(bool $blocking): static;

    /**
     * 设置超时时间
     *
     * @param float $timeout 超时时间（秒）
     *
     * @author Verdient。
     */
    public function timeout(float $timeout): static;

    /**
     * 监听信号
     *
     * @param int $signo 信号
     * @param Closure|int $handler 回调函数
     *
     * @author Verdient。
     */
    public function signal(int $signo, Closure|int $handler = SIG_IGN): static;

    /**
     * 杀死进程
     *
     * @param int $signo 信号
     *
     * @author Verdient。
     */
    public function kill(int $signo = SIGTERM): bool;

    /**
     * 获取进程ID
     *
     * @author Verdient。
     */
    public function getPid(): ?int;

    /**
     * 获取套接字
     *
     * @author Verdient。
     */
    public function getSocket(): ?Socket;
}
