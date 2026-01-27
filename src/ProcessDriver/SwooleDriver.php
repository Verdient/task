<?php

declare(strict_types=1);

namespace Verdient\Task\ProcessDriver;

use FilesystemIterator;
use Override;
use Swoole\Process as SwooleProcess;
use Verdient\Task\Process\ChildrenProcess;
use Verdient\Task\Process\HandlerInterface;
use Verdient\Task\Socket;
use Verdient\Task\Stream;

/**
 * Swoole驱动
 *
 * @author Verdient。
 */
class SwooleDriver extends AbstractProcessDriver
{
    /**
     * 进程对象
     *
     * @author Verdient。
     */
    protected ?SwooleProcess $process = null;

    /**
     * 套接字
     *
     * @author Verdient。
     */
    protected ?Socket $socket = null;

    /**
     * @author Verdient。
     */
    #[Override]
    public function start(HandlerInterface $handler): void
    {
        $fdNames1 = [];

        foreach (new FilesystemIterator('/proc/self/fd') as $splFileInfo) {
            $fdNames1[] = readlink($splFileInfo->getPathname());
        }

        [$read, $write] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $fdNames2 = [];

        foreach (new FilesystemIterator('/proc/self/fd') as $splFileInfo) {
            $fdNames2[] = readlink($splFileInfo->getPathname());
        }

        $keepedFileDescriptorNames = array_diff($fdNames2, $fdNames1);

        $handler = (function (SwooleProcess $swooleProcess) use ($handler, $write, $read, $keepedFileDescriptorNames) {
            $driver = new static;
            $driver->process = $swooleProcess;
            $driver->socket = new Socket(new Stream($write));
            fclose($read);
            $childrenProcess = new ChildrenProcess($driver);
            $childrenProcess->keepedFileDescriptorNames($keepedFileDescriptorNames);
            $handler->handle($childrenProcess);
        })->bindTo(null);

        $this->process = new SwooleProcess($handler);

        $this->process->start();

        fclose($write);

        $this->socket = new Socket(new Stream($read));
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function daemonize(bool $noChdir = true, bool $noClose = true): void
    {
        $this->process->daemon($noChdir, $noClose);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getSocket(): ?Socket
    {
        return $this->socket;
    }
}
