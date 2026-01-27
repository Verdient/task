<?php

declare(strict_types=1);

namespace Verdient\Task\Dispatcher;

use Override;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Verdient\Task\Event\BeforeConsume;
use Verdient\Task\Event\Consumed;
use Verdient\Task\Event\FailedToConsume;
use Verdient\Task\Event\FailedToDispachEvent;
use Verdient\Task\Event\WorkerStarted;
use Verdient\Task\Event\WorkerStopped;
use Verdient\Task\Packet;
use Verdient\Task\Payload;
use Verdient\Task\Process\ChildrenProcess;
use Verdient\Task\Process\HandlerInterface;
use Verdient\Task\TaskInterface;

use function Swoole\Coroutine\run;

/**
 * 工作进程处理程序
 *
 * @author Verdient。
 */
class WorkerHandler implements HandlerInterface
{
    /**
     * @param TaskInterface $task 任务
     * @param LoggerInterface $logger 日志记录器
     * @param int $masterPid 主进程编号
     * @param bool $enableCoroutine 是否启用协程
     * @param ?EventDispatcherInterface $eventDispatcher 事件调度器
     *
     * @author Verdient。
     */
    public function __construct(
        protected TaskInterface $task,
        protected LoggerInterface $logger,
        protected int $masterPid,
        protected bool $enableCoroutine,
        protected ?EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * @author Verdient。
     */
    #[Override]
    public function handle(ChildrenProcess $process): void
    {
        $this->onWorkerStarted($process);

        $masterPid = $this->masterPid;

        $logger = $this->logger;

        $process->blocking(true);

        $process->timeout(1);

        $shouldExit = false;

        $process->signal(SIGINT, function () use (&$shouldExit) {
            $shouldExit = true;
        });

        $process->signal(SIGTERM, function () use (&$shouldExit) {
            $shouldExit = true;
        });

        $lastSendAt = 0;

        $socket = $process->getSocket();

        $taskClass = $this->task::class;

        if ($this->eventDispatcher) {
            $consume = function (Payload $payload) use ($taskClass) {
                $startAt = microtime(true);
                $endAt = null;
                try {
                    $this->dispatchEvent(new BeforeConsume($this->task, $payload));
                    $startAt = microtime(true);
                    $taskClass::consume($payload);
                    $endAt = microtime(true);
                    $this->dispatchEvent(new Consumed($this->task, $payload, $startAt, $endAt));
                } catch (\Throwable $e) {
                    if ($endAt === null) {
                        $endAt = microtime(true);
                    }
                    $payload->logger()->error($e);
                    $this->dispatchEvent(new FailedToConsume($this->task, $payload, $e, $startAt, $endAt));
                }
            };
        } else {
            $consume = function (Payload $payload) use ($taskClass) {
                try {
                    $taskClass::consume($payload);
                } catch (\Throwable $e) {
                    $payload->logger()->error($e);
                }
            };
        }

        if ($this->enableCoroutine) {
            $consume = fn(Payload $payload) => run($consume, $payload);
        }

        while (true) {

            $content = $socket->read();

            $hasContent = $content !== null && $content !== '';

            if ($hasContent) {
                try {
                    $packet = unserialize($content);
                    if ($packet instanceof Packet) {
                        $payload = new Payload($packet->data, $this->task);
                        $payload->setLogger($logger);
                        $consume($payload);
                    }
                } catch (\Throwable) {
                }
            }

            if ($shouldExit || !posix_kill($masterPid, 0)) {
                $this->onWorkerStopped($process);
                error_reporting(E_ALL & ~E_NOTICE);
                break;
            }

            $microtime = microtime(true);

            if (
                $hasContent
                || ($lastSendAt > 0 && ($microtime - $lastSendAt) >= 1)
            ) {
                $socket->write((string) memory_get_peak_usage(true));
                $lastSendAt = $microtime;
            }
        }
    }

    /**
     * 分发事件
     *
     * @param object $event 事件
     *
     * @author Verdient。
     */
    protected function dispatchEvent(object $event): void
    {
        try {
            $this->eventDispatcher->dispatch($event);
        } catch (\Throwable $e) {
            try {
                $this->eventDispatcher->dispatch(new FailedToDispachEvent($this->task, $e, $event));
            } catch (\Throwable) {
            }
        }
    }

    /**
     * 工作进程启动
     *
     * @param ChildrenProcess $process 工作进程
     *
     * @author Verdient。
     */
    protected function onWorkerStarted(ChildrenProcess $process): void
    {
        if ($this->eventDispatcher) {
            if ($this->enableCoroutine) {
                run(fn() => $this->dispatchEvent(new WorkerStarted($this->task, $process)));
            } else {
                $this->dispatchEvent(new WorkerStarted($this->task, $process));
            }
        }
    }

    /**
     * 工作进程停止
     *
     * @param ChildrenProcess $process 工作进程
     *
     * @author Verdient。
     */
    protected function onWorkerStopped(ChildrenProcess $process): void
    {
        if ($this->eventDispatcher) {
            if ($this->enableCoroutine) {
                run(fn() => $this->dispatchEvent(new WorkerStopped($this->task, $process)));
            } else {
                $this->dispatchEvent(new WorkerStopped($this->task, $process));
            }
        }
    }
}
