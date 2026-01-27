<?php

declare(strict_types=1);

namespace Verdient\Task\Process;

use BadFunctionCallException;
use Closure;
use Override;
use Verdient\Task\ProcessDriver\ProcessDriverInterface;
use Verdient\Task\Socket;

/**
 * 进程
 *
 * @author Verdient。
 */
class Process implements ParentSideProcessInterface
{
    /**
     * 进程驱动
     *
     * @author Verdient。
     */
    protected ProcessDriverInterface $driver;

    /**
     * 进程名称
     *
     * @author Verdient。
     */
    protected ?string $name;

    /**
     * 守护进程
     *
     * @author Verdient。
     */
    protected bool $daemonize = false;

    /**
     * 是否清空文件描述符
     *
     * @author Verdient。
     */
    protected bool $clearFileDescriptors = false;

    /**
     * 是否阻塞
     *
     * @author Verdient。
     */
    protected bool $blocking = false;

    /**
     * 超时时间
     *
     * @author Verdient。
     */
    protected ?float $timeout = null;

    /**
     * 注册的信号
     *
     * @author Verdient。
     */
    protected array $signals = [];

    /**
     * 进程编号
     *
     * @author Verdient。
     */
    protected ?int $pid = null;

    /**
     * @param HandlerInterface $handler 处理函数
     * @param ProcessDriverInterface $driver 进程驱动
     *
     * @author Verdient。
     */
    public function __construct(protected HandlerInterface $handler, ProcessDriverInterface $driver)
    {
        $this->driver = clone $driver;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function name(string $name): static
    {
        if ($this->isRunning()) {
            throw new BadFunctionCallException('The name() method can only be called before the process start.');
        }

        $this->name = $name;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function daemonize(bool $daemonize = true): static
    {
        if ($this->isRunning()) {
            throw new BadFunctionCallException('The daemonize() method can only be called before the process start.');
        }

        $this->daemonize = $daemonize;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function clearFileDescriptors(): static
    {
        if ($this->isRunning()) {
            throw new BadFunctionCallException('The clearFileDescriptors() method can only be called before the process start.');
        }

        $this->clearFileDescriptors = true;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function keepFileDescriptors(): static
    {
        if ($this->isRunning()) {
            throw new BadFunctionCallException('The keepFileDescriptors() method can only be called before the process start.');
        }

        $this->clearFileDescriptors = false;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function blocking(bool $blocking): static
    {
        if ($this->isRunning()) {
            throw new BadFunctionCallException('The blocking() method can only be called before the process start.');
        }

        $this->blocking = $blocking;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function timeout(float $timeout): static
    {
        if ($this->isRunning()) {
            throw new BadFunctionCallException('The timeout() method can only be called before the process start.');
        }

        $this->timeout = $timeout;

        return $this;
    }

    /**
     * 监听信号
     *
     * @param int $signo 信号
     * @param Closure|int $handler 回调函数
     *
     * @author Verdient。
     */
    public function signal(int $signo, Closure|int $handler = SIG_IGN): static
    {
        if ($this->isRunning()) {
            throw new BadFunctionCallException('The signal() method can only be called before the process start.');
        }

        $this->signals[$signo] = $handler;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function start(): void
    {
        $handlers = [];

        if ($bootHandler = $this->driver->bootHandler()) {
            $handlers[] = $bootHandler;
        }

        $handlers[] = new ProcessHandler(
            $this->handler,
            $this->name,
            $this->daemonize,
            $this->signals,
            $this->clearFileDescriptors,
            $this->blocking,
            $this->timeout,
        );

        $handler = count($handlers) > 1 ? new CompositeHandler($handlers) : $handlers[0];

        $this->driver->start($handler);

        $this->pid = (int) $this->getSocket()->read();

        $this->getSocket()->getStream()->blocking($this->blocking);

        if ($this->timeout) {
            $this->getSocket()->getStream()->timeout($this->timeout);
        }
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getSocket(): ?Socket
    {
        return $this->driver->getSocket();
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function kill(int $signo = SIGTERM): bool
    {
        if ($pid = $this->getPid()) {
            return posix_kill($pid, $signo);
        }
        return false;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getPid(): ?int
    {
        return $this->pid;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function isRunning(): bool
    {
        if ($pid = $this->getPid()) {
            return posix_kill($pid, 0);
        }

        return false;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getHandler(): HandlerInterface
    {
        return $this->handler;
    }
}
