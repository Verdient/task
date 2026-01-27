<?php

declare(strict_types=1);

namespace Verdient\Task\Process;

use Override;

/**
 * 进程处理程序
 *
 * @author Verdient。
 */
class ProcessHandler implements HandlerInterface
{
    /**
     * @param HandlerInterface $handler 处理程序
     * @param string $name 进程名称
     * @param bool $daemonize 是否以守护进程运行
     * @param array $signals 信号处理函数
     * @param bool $clearFileDescriptors 是否清空文件描述符
     * @param bool $blocking 是否阻塞
     * @param float $timeout 超时时间（秒）
     *
     * @author Verdient。
     */
    public function __construct(
        protected HandlerInterface $handler,
        protected ?string $name,
        protected bool $daemonize,
        protected array $signals,
        protected bool $clearFileDescriptors,
        protected bool $blocking,
        protected ?float $timeout
    ) {}

    /**
     * @author Verdient。
     */
    #[Override]
    public function handle(ChildrenProcess $process): void
    {
        pcntl_async_signals(true);

        if ($this->daemonize) {
            $process->daemonize();
        }

        $process->getSocket()->write((string) getmypid());

        if ($this->name) {
            $process->name($this->name);
        }

        foreach ($this->signals as $signal => $signalHandler) {
            pcntl_signal($signal, $signalHandler);
        }

        if ($this->clearFileDescriptors) {
            $process->clearFileDescriptors();
        }

        $process->blocking($this->blocking);

        if ($this->blocking && $this->timeout !== null) {
            $process->timeout($this->timeout);
        }

        $this->handler->handle($process);
    }
}
