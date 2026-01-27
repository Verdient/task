<?php

declare(strict_types=1);

namespace Verdient\Task\ProcessDriver;

use FilesystemIterator;
use Override;
use RuntimeException;
use Verdient\Task\Process\ChildrenProcess;
use Verdient\Task\Process\HandlerInterface;
use Verdient\Task\Socket;
use Verdient\Task\Stream;

/**
 * Fork驱动
 *
 * @author Verdient。
 */
class ForkDriver extends AbstractProcessDriver
{
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

        $pid = pcntl_fork();

        if ($pid === -1) {
            fclose($read);
            fclose($write);
            throw new RuntimeException('Fork failed.');
        } else if ($pid > 0) {

            fclose($write);

            $this->socket = new Socket(new Stream($read));

            pcntl_signal(SIGCHLD, function () {
                while (pcntl_waitpid(-1, $status, WNOHANG) > 0) {
                }
            });
        } else {
            fclose($read);

            $driver = new static;
            $driver->socket = new Socket(new Stream($write));

            $childrenProcess = new ChildrenProcess($driver);
            $childrenProcess->keepedFileDescriptorNames($keepedFileDescriptorNames);

            $handler->handle($childrenProcess);

            pcntl_signal(SIGTERM, SIG_DFL);
            $childrenProcess->kill();
        }
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function daemonize(bool $noChdir = true, bool $noClose = true): void
    {
        $pid = pcntl_fork();

        if ($pid < 0) {
            throw new RuntimeException('First fork failed');
        }

        if ($pid > 0) {
            pcntl_signal(SIGTERM, SIG_DFL);
            posix_kill(getmypid(), SIGTERM);
            return;
        }

        if (posix_setsid() < 0) {
            throw new RuntimeException('setsid failed');
        }

        $pid = pcntl_fork();

        if ($pid < 0) {
            throw new RuntimeException('Second fork failed');
        }

        if ($pid > 0) {
            pcntl_signal(SIGTERM, SIG_DFL);
            posix_kill(getmypid(), SIGTERM);
            return;
        }

        if (!$noChdir) {
            if (!chdir('/')) {
                throw new RuntimeException('chdir to / failed.');
            }
        }

        umask(0);

        if (!$noClose) {
            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);

            $stdin = fopen('/dev/null', 'r');
            $stdout = fopen('/dev/null', 'ab');
            $stderr = fopen('/dev/null', 'ab');

            if (!$stdin || !$stdout || !$stderr) {
                throw new RuntimeException('Failed to redirect standard streams to /dev/null');
            }
        }
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
