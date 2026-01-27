<?php

declare(strict_types=1);

namespace Verdient\Task\Process;

use Closure;
use FilesystemIterator;
use Override;
use Verdient\Task\FFI;
use Verdient\Task\ProcessDriver\ProcessDriverInterface;
use Verdient\Task\Socket;

/**
 * 子进程
 *
 * @author Verdient。
 */
class ChildrenProcess implements ChildrenSideProcessInterface
{
    /**
     * 进程驱动
     *
     * @author Verdient。
     */
    protected ProcessDriverInterface $driver;

    /**
     * 保留的文件描述符名称
     *
     * @author Verdient。
     */
    protected array $keepedFileDescriptorNames = [];

    /**
     * @param ProcessDriverInterface $driver 进程驱动
     *
     * @author Verdient。
     */
    public function __construct(ProcessDriverInterface $driver)
    {
        $this->driver = clone $driver;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function name(string $name): static
    {
        cli_set_process_title($name);

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function clearFileDescriptors(): static
    {
        foreach (new FilesystemIterator('/proc/self/fd') as $splFileInfo) {
            $fd = (int) $splFileInfo->getBasename();
            if ($fd < 3) {
                continue;
            }

            $name = readlink($splFileInfo->getPathname());

            if (in_array($name, $this->keepedFileDescriptorNames)) {
                continue;
            }

            FFI::close($fd);
        }

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function keepedFileDescriptorNames(array $names): static
    {
        foreach ($names as $name) {
            $this->keepedFileDescriptorNames[] = $name;
        }

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function blocking(bool $blocking): static
    {
        $this->driver->getSocket()->getStream()->blocking($blocking);

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function timeout(float $timeout): static
    {
        $this->driver->getSocket()->getStream()->timeout($timeout);

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
        pcntl_signal($signo, $handler);

        return $this;
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
        return getmypid() ?: null;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function daemonize(): void
    {
        $this->driver->daemonize();
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getSocket(): ?Socket
    {
        return $this->driver->getSocket();
    }
}
