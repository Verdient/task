<?php

declare(strict_types=1);

namespace Verdient\Task\ProcessDriver;

use Composer\Autoload\ClassLoader;
use RuntimeException;
use Override;
use ReflectionClass;
use Swoole\Process;
use Verdient\Task\Process\HandlerInterface;
use Verdient\Task\Socket;
use Verdient\Task\Stream;

/**
 * ProcOpen 驱动
 *
 * @author Verdient。
 */
class ProcOpenDriver extends AbstractProcessDriver
{
    /**
     * 套接字
     *
     * @author Verdient。
     */
    public ?Socket $socket = null;

    /**
     * 进程资源
     *
     * @author Verdient。
     */
    protected $process = null;

    /**
     * @author Verdient。
     */
    #[Override]
    public function start(HandlerInterface $handler): void
    {
        $basePath = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 3);

        $descriptors = [
            3 => ['socket']
        ];

        $dir = '/proc/self/fd';

        foreach (scandir($dir) as $fd) {
            if (!ctype_digit($fd)) {
                continue;
            }

            $fd = (int) $fd;

            if ($fd < 4) {
                continue;
            }

            if (file_exists($dir . '/' . $fd)) {
                $descriptors[$fd] = ['file', '/dev/null', 'r'];
            }
        }

        $driver = new static;

        $driverTmpPath = tempnam(sys_get_temp_dir(), md5(__METHOD__) . '-');

        $handlerTmpFile = tempnam(sys_get_temp_dir(), md5(__METHOD__) . '-');

        file_put_contents($driverTmpPath, serialize($driver));

        file_put_contents($handlerTmpFile, serialize($handler));

        $this->process = proc_open([
            PHP_BINARY,
            __DIR__ . '/handler.php',
            $driverTmpPath,
            $handlerTmpFile,
            $basePath
        ], $descriptors, $pipes);

        if (!is_resource($this->process)) {
            throw new RuntimeException('proc_open failed.');
        }

        pcntl_signal(SIGCHLD, function () {
            if (class_exists(Process::class)) {
                while (Process::wait(false) !== false) {
                }
            } else {
                while (pcntl_waitpid(-1, $status, WNOHANG) > 0) {
                }
            }
        });

        $this->socket = new Socket(new Stream($pipes[3]));

        for ($i = 4; $i < count($pipes); $i++) {
            fclose($pipes[$i]);
        }
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function daemonize(bool $noChdir = true, bool $noClose = true): void {}

    /**
     * @author Verdient。
     */
    #[Override]
    public function getSocket(): ?Socket
    {
        return $this->socket;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function bootHandler(): ?HandlerInterface
    {
        return new ProcOpenBootHandler(3);
    }
}
