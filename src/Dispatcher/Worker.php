<?php

declare(strict_types=1);

namespace Verdient\Task\Dispatcher;

use Verdient\Task\Packet;
use Verdient\Task\Process\HandlerInterface;
use Verdient\Task\Process\Process;
use Verdient\Task\ProcessDriver\ProcessDriverInterface;

/**
 * 工作进程
 *
 * @author Verdient。
 */
class Worker
{
    /**
     * 进程
     *
     * @author Verdient。
     */
    protected ?Process $process = null;

    /**
     * 闲置开始时间
     *
     * @author Verdient。
     */
    protected int $idleStartAt = 0;

    /**
     * 内存占用峰值
     *
     * @author verdient。
     */
    protected int $peakMemoryUsage = 0;

    /**
     * 进程编号
     *
     * @author Verdient。
     */
    protected ?int $pid;

    /**
     * @param string $name 工作进程名称
     * @param int $maxIdleSeconds 最大空闲的秒数
     * @param ProcessDriverInterface $driver 进程驱动
     * @param ?HandlerInterface $handler 处理程序
     *
     * @author Verdient。
     */
    public function __construct(
        protected string $name,
        protected int $maxIdleSeconds,
        protected ProcessDriverInterface $driver,
        protected ?HandlerInterface $handler
    ) {}

    /**
     * 推送任务
     *
     * @param Packet $packet 任务数据包
     *
     * @author Verdient。
     */
    public function push(Packet $packet): bool
    {
        if (!$this->process) {
            return false;
        }

        if ($this->idleStartAt === 0) {
            return false;
        }

        if ($this->process->getSocket()->write($packet)) {
            $this->idleStartAt = 0;
            return true;
        }

        return false;
    }

    /**
     * 获取任务进程是否正在运行
     *
     * @author Verdient。
     */
    public function isRunning(): bool
    {
        if ($this->process === null) {
            return false;
        }

        if ($this->process->isRunning() === false) {
            $this->process = null;
            return false;
        }

        return true;
    }

    /**
     * 获取内存占用峰值
     *
     * @author Verdient。
     */
    public function getPeakMemoryUsage(): int
    {
        return $this->peakMemoryUsage;
    }

    /**
     * 启动工作进程
     *
     * @author Verdient。
     */
    public function start(): void
    {
        $this->process = (new Process($this->handler, $this->driver))
            ->name($this->name)
            ->blocking(false)
            ->daemonize();

        $this->process->start();

        $this->idleStartAt = time();
    }

    /**
     * 获取进程编号
     *
     * @author Verdient。
     */
    public function getPid(): ?int
    {
        if ($this->process) {
            return $this->pid = $this->process->getPid();
        }

        return $this->pid;
    }

    /**
     * 终止进程
     *
     * @author Verdient。
     */
    public function terminate(): void
    {
        if ($this->process) {
            $this->process->kill(SIGTERM);
            $this->process = null;
        }
    }

    /**
     * 刷新状态
     *
     * @author Verdient。
     */
    public function flush(): void
    {
        if (!$this->isRunning()) {
            return;
        }

        if (
            $this->idleStartAt > 0
            && (time() - $this->idleStartAt) >= $this->maxIdleSeconds
        ) {
            $this->terminate();
            return;
        }

        $peakMemoryUsage = 0;

        while (true) {
            $content = $this->process->getSocket()->read();

            if ($content === null) {
                break;
            }

            if (is_numeric($content)) {
                $peakMemoryUsage = (int) $content;
            }
        }

        if ($peakMemoryUsage > $this->peakMemoryUsage) {
            $this->peakMemoryUsage = $peakMemoryUsage;
        }

        if ($this->idleStartAt === 0 && $peakMemoryUsage > 0) {
            $this->idleStartAt = time();
        }
    }

    /**
     * 获取进程是否空闲
     *
     * @author Verdient。
     */
    public function isIdle(): bool
    {
        return $this->idleStartAt > 0;
    }
}
