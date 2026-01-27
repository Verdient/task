<?php

declare(strict_types=1);

namespace Verdient\Task\Dispatcher;

use Closure;
use InvalidArgumentException;
use Override;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Swoole\Coroutine;
use Swoole\Runtime;
use Verdient\Logger\Logger;
use Verdient\Logger\StdoutLogger;
use Verdient\Task\Event\BeforeProduce;
use Verdient\Task\Event\BeforeStart;
use Verdient\Task\Event\BeforeStop;
use Verdient\Task\Event\FailedToDispachEvent;
use Verdient\Task\Event\FailedToProduce;
use Verdient\Task\Event\FailedToStart;
use Verdient\Task\Event\FailedToStop;
use Verdient\Task\Event\Produced;
use Verdient\Task\Event\Started;
use Verdient\Task\Event\Stopped;
use Verdient\Task\TaskInterface;

use function Swoole\Coroutine\run;

/**
 * 抽象调度器
 *
 * @author Verdient。
 */
abstract class AbstractDispatcher implements DispatcherInterface
{
    /**
     * 是否启用协程
     *
     * @author Verdient。
     */
    protected bool $enableCoroutine = false;

    /**
     * 标识符
     *
     * @author Verdient。
     */
    protected string $identifier;

    /**
     * 生产记录器
     *
     * @author Verdient。
     */
    protected ?LoggerInterface $produceLogger = null;

    /**
     * 消费记录器
     *
     * @author Verdient。
     */
    protected ?LoggerInterface $consumeLogger = null;

    /**
     * 父进程编号
     *
     * @author Verdient。
     */
    protected ?int $masterPid = null;

    /**
     * 事件调度器
     *
     * @author Verdient。
     */
    protected ?EventDispatcherInterface $eventDispatcher = null;

    /**
     * 是否应该退出
     *
     * @author Verdient。
     */
    protected bool $shouldExit = false;

    /**
     * @param TaskInterface $task 任务
     *
     * @author Verdient。
     */
    public function __construct(public readonly TaskInterface $task)
    {
        $this->identifier = str_replace('\\', '.', $task::class);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public static function create(TaskInterface $task): static
    {
        return new static($task);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function enableCoroutine(): static
    {
        if (!class_exists('Swoole\Coroutine')) {
            throw new RuntimeException('Swoole extension is required.');
        }

        $this->enableCoroutine = true;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function disableCoroutine(): static
    {
        $this->enableCoroutine = false;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function setIdentifier(string $value): static
    {
        $this->identifier = $value;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function setProduceLogger(?LoggerInterface $value): static
    {
        $this->produceLogger = $value;

        $this->task->setLogger($value);

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getProduceLogger(): ?LoggerInterface
    {
        if ($this->produceLogger === null) {
            $this->setProduceLogger(new Logger(new StdoutLogger, $this->getIdentifier() . '::Produce'));
        }

        return $this->produceLogger;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function setConsumeLogger(?LoggerInterface $value): static
    {
        $this->consumeLogger = $value;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getConsumeLogger(): ?LoggerInterface
    {
        if ($this->consumeLogger === null) {
            $this->consumeLogger = new Logger(new StdoutLogger, $this->getIdentifier() . '::Consume');
        }

        return $this->consumeLogger;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function setMasterPid(?int $masterPid): static
    {
        if ($masterPid < 1) {
            throw new InvalidArgumentException("Master PID must be a positive integer, {$masterPid} given");
        }

        $this->masterPid = $masterPid;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getMasterPid(): ?int
    {
        return $this->masterPid;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): static
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getEventDispatcher(): ?EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * 是否应该退出
     *
     * @author Verdient。
     */
    protected function shouldExit(): bool
    {
        if ($this->shouldExit) {
            return true;
        }

        $masterPid = $this->getMasterPid();

        if ($masterPid === null) {
            return false;
        }

        return !posix_kill($this->masterPid, 0);
    }

    /**
     * 注册信号
     *
     * @author Verdient。
     */
    protected function registerSignal(): void
    {
        pcntl_signal(SIGINT, fn() => $this->shouldExit = true);
        pcntl_signal(SIGTERM, fn() => $this->shouldExit = true);
        pcntl_async_signals(true);
    }

    /**
     * 启动时的回调
     *
     * @author Verdient。
     */
    protected function onStart(): void
    {
        $onStart = function () {
            $startAt = microtime(true);
            $endAt = null;
            try {
                $this->dispatchEvent(new BeforeStart($this->task));
                $startAt = microtime(true);
                $this->task->onStart();
                $endAt = microtime(true);
                $this->dispatchEvent(new Started($this->task, $startAt, $endAt));
            } catch (\Throwable $e) {
                if ($endAt === null) {
                    $endAt = microtime(true);
                }
                $this->getProduceLogger()->error($e);
                $this->dispatchEvent(new FailedToStart($this->task, $e, $startAt, $endAt));
                throw $e;
            }
        };

        if ($this->enableCoroutine) {
            $this->runInCoroutine($onStart);
        } else {
            $onStart();
        }
    }

    /**
     * 停止时的回调
     *
     * @author Verdient。
     */
    protected function onStop(): void
    {
        $onStop = function () {
            $startAt = microtime(true);
            $endAt = null;
            try {
                $this->dispatchEvent(new BeforeStop($this->task));
                $startAt = microtime(true);
                $this->task->onStop();
                $endAt = microtime(true);
                $this->dispatchEvent(new Stopped($this->task, $startAt, $endAt));
            } catch (\Throwable $e) {
                if ($endAt === null) {
                    $endAt = microtime(true);
                }
                $this->getProduceLogger()->error($e);
                $this->dispatchEvent(new FailedToStop($this->task, $e, $startAt, $endAt));
                throw $e;
            }
        };

        if ($this->enableCoroutine) {
            $this->runInCoroutine($onStop);
        } else {
            $onStop();
        }
    }

    /**
     * 生产数据内部方法
     *
     * @author Verdient。
     */
    protected function produceInternal(): ?array
    {
        $startAt = microtime(true);
        $endAt = null;
        try {
            $this->dispatchEvent(new BeforeProduce($this->task));
            $startAt = microtime(true);
            $data = $this->task->produce();
            $endAt = microtime(true);
            $this->dispatchEvent(new Produced($this->task, $data, $startAt, $endAt));
            return $data;
        } catch (\Throwable $e) {
            if ($endAt === null) {
                $endAt = microtime(true);
            }
            $this->getProduceLogger()->error($e);
            $this->dispatchEvent(new FailedToProduce($this->task, $e, $startAt, $endAt));
            return null;
        }
    }

    /**
     * 生产数据
     *
     * @author Verdient。
     */
    protected function produce(): ?array
    {
        if ($this->enableCoroutine) {
            return $this->runInCoroutine(fn() => $this->produceInternal());
        } else {
            return $this->produceInternal();
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
        if (!$eventDispatcher = $this->getEventDispatcher()) {
            return;
        }
        try {
            $eventDispatcher->dispatch($event);
        } catch (\Throwable $e) {
            try {
                $eventDispatcher->dispatch(new FailedToDispachEvent($this->task, $e, $event));
            } catch (\Throwable) {
            }
        }
    }

    /**
     * 获取空闲间隔
     *
     * @author Verdient。
     */
    protected function idleGap(): int|float
    {
        try {
            if ($this->enableCoroutine) {
                return $this->runInCoroutine(fn() => $this->task->idleGap());
            } else {
                return $this->task->idleGap();
            }
        } catch (\Throwable $e) {
            $this->getProduceLogger()->error($e);
            return 1;
        }
    }

    /**
     * 在协程中执行
     *
     * @param Closure $callback 回调
     * @param int $flags 标志
     *
     * @author Verdient。
     */
    protected function runInCoroutine(Closure $callback, int $flags = SWOOLE_HOOK_ALL): mixed
    {
        if (Coroutine::getCid() > 0) {
            return $callback();
        }

        Runtime::enableCoroutine($flags);

        $result = null;

        $throwable = null;

        run(function () use ($callback, &$result, &$throwable) {
            try {
                $result = $callback();
            } catch (\Throwable $e) {
                $throwable = $e;
            }
        });

        Runtime::enableCoroutine(0);

        if ($throwable) {
            throw $throwable;
        }

        return $result;
    }
}
