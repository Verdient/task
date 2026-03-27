<?php

declare(strict_types=1);

namespace Verdient\Task\Dispatcher;

use Ds\Map;
use InvalidArgumentException;
use Override;
use RuntimeException;
use Verdient\Task\Event\WorkerCreated;
use Verdient\Task\Event\WorkerRemoved;
use Verdient\Task\Packet;
use Verdient\Task\Process\CompositeHandler;
use Verdient\Task\Process\HandlerInterface;
use Verdient\Task\ProcessDriver\ForkDriver;
use Verdient\Task\ProcessDriver\ProcessDriverInterface;
use Verdient\Task\ProcessDriver\ProcOpenDriver;
use Verdient\Task\ProcessDriver\SwooleDriver;
use Verdient\Task\TaskInterface;

/**
 * 工作进程调度器
 *
 * @author Verdient。
 */
class WorkersDispatcher extends AbstractDispatcher
{
    /**
     * 工作进程名称
     *
     * @author Verdient。
     */
    protected ?string $name = null;

    /**
     * 最大进程数量
     *
     * @author Verdient。
     */
    protected int $maxWorkersNums = 0;

    /**
     * 最大闲置时间
     *
     * @author Verdient。
     */
    protected int $maxIdleSeconds = 15;

    /**
     * 峰值内存占用
     *
     * @author Verdient。
     */
    protected int $peakMemoryUsage = 0;

    /**
     * 单个CPU最大可承载的进程数量
     *
     * @author Verdient。
     */
    protected int $maxWorkersNumsPerCPU = 0;

    /**
     * CPU最大可承载的进程数量
     *
     * @author Verdient。
     */
    protected ?int $cpuCapacity = null;

    /**
     * @var Map<Worker,Worker> 工作进程集合
     *
     * @author Verdient。
     */
    protected Map $workers;

    /**
     * @var Map<Worker,Worker> 空闲的工作进程
     *
     * @author Verdient。
     */
    protected Map $idleWorkers;

    /**
     * 进程驱动
     *
     * @author Verdient。
     */
    protected ?ProcessDriverInterface $processDriver = null;

    /**
     * CPU数量
     *
     * @author Verdient。
     */
    protected ?int $cpuNum = null;

    /**
     * 工作进程启动处理程序
     *
     * @author Verdient。
     */
    protected ?HandlerInterface $bootHandler = null;

    /**
     * @author Verdient。
     */
    public function __construct(TaskInterface $task)
    {
        parent::__construct($task);
        $this->workers = new Map();
        $this->idleWorkers = new Map();
    }

    /**
     * 添加工作进程
     *
     * @param Worker $worker 工作进程
     * @param int $maxNum 最大进程数量
     *
     * @author Verdient。
     */
    protected function add(Worker $worker, int $maxNum): static
    {
        if (!$this->workers->offsetExists($worker)) {
            $this->workers->offsetSet($worker, $worker);
            $this->dispatchEvent(new WorkerCreated(
                $this->task,
                $worker->getPid(),
                $this->count(),
                $this->idleWorkers->count(),
                $maxNum
            ));
        }

        return $this;
    }

    /**
     * 移除工作进程
     *
     * @param Worker $worker 工作进程
     *
     * @author Verdient。
     */
    protected function remove(Worker $worker): static
    {
        if ($this->workers->offsetExists($worker)) {
            $this->workers->offsetUnset($worker);
            $this->idleWorkers->offsetUnset($worker);
            $this->dispatchEvent(new WorkerRemoved(
                $this->task,
                $worker->getPid(),
                $this->count(),
                $this->idleWorkers->count()
            ));
        }

        return $this;
    }

    /**
     * 将工作进程设置为占用
     *
     * @param Worker $worker 工作进程
     *
     * @author Verdient。
     */
    protected function setOccupied(Worker $worker): static
    {
        unset($this->idleWorkers[$worker]);

        return $this;
    }

    /**
     * 将工作进程设置为闲置
     *
     * @param Worker $worker 工作进程
     *
     * @author Verdient。
     */
    protected function setIdle(Worker $worker)
    {
        if (
            $this->workers->offsetExists($worker)
            && !($this->idleWorkers->offsetExists($worker))
        ) {
            $this->idleWorkers->offsetSet($worker, $worker);
        }

        return $this;
    }

    /**
     * 获取进程数量
     *
     * @author Verdient。
     */
    protected function count(): int
    {
        return $this->workers->count();
    }

    /**
     * 获取是否可以启动新的进程
     *
     * @author Verdient。
     */
    protected function startable(): bool
    {
        return $this->count() < $this->maxNum();
    }

    /**
     * 获取是否可推送
     *
     * @author Verdient。
     */
    protected function pushable(): bool
    {
        if ($this->idleWorkers->count() > 0) {
            return true;
        }

        return $this->startable();
    }

    /**
     * 推送任务到工作进程
     *
     * @param Packet $message 任务数据包
     *
     * @author Verdien。
     */
    protected function push(Packet $packet): bool
    {
        $count = $this->idleWorkers->count();

        if ($count > 0) {

            if ($count < 10) {
                $idleWorkers = [];

                foreach ($this->idleWorkers->getIterator() as $worker) {
                    $idleWorkers[] = $worker;
                }

                if (count($idleWorkers) < 10) {
                    shuffle($idleWorkers);
                }

                foreach ($idleWorkers as $worker) {
                    if ($worker->push($packet)) {
                        $this->setOccupied($worker);
                        return true;
                    }
                }
            } else {
                foreach ($this->idleWorkers->getIterator() as $worker) {
                    if ($worker->push($packet)) {
                        $this->setOccupied($worker);
                        return true;
                    }
                }
            }
        }

        $handlers = [];

        if ($this->bootHandler !== null) {
            $handlers[] = $this->bootHandler;
        }

        $handlers[] = new WorkerHandler(
            $this->task,
            $this->getConsumeLogger(),
            getmypid(),
            $this->enableCoroutine,
            $this->getEventDispatcher()
        );

        $handler = count($handlers) > 1 ? new CompositeHandler($handlers) : $handlers[0];

        $attempts = 0;

        $maxNum = $this->maxNum();

        while ($this->count() < $maxNum) {
            if (++$attempts > 3) {
                break;
            }

            $worker = new Worker(
                $this->getName(),
                $this->getMaxIdleSeconds(),
                $this->getProcessDriver(),
                $handler
            );

            $worker->start();

            $this->add($worker, $maxNum);

            if ($worker->push($packet)) {
                $this->setOccupied($worker);
                return true;
            }
        }

        return false;
    }

    /**
     * 获取可用内存最大可承载的进程数量
     *
     * @author Verdient。
     */
    protected function availableMemoryCapacity(): int
    {
        if ($this->peakMemoryUsage === 0) {
            $peakMemoryUsage = memory_get_peak_usage(true) * 2;
        } else {
            $peakMemoryUsage = $this->peakMemoryUsage;
        }

        $memoryAvailable = 0;

        foreach (file('/proc/meminfo', FILE_IGNORE_NEW_LINES) as $line) {
            if (str_starts_with($line, 'MemAvailable')) {
                $memoryAvailable = (int) filter_var($line, FILTER_SANITIZE_NUMBER_INT);
                break;
            }
        }

        return (int) floor($memoryAvailable * 1024 / $peakMemoryUsage);
    }

    /**
     * 获取CPU数量
     *
     * @author Verdient。
     */
    protected function getCpuNum(): int
    {
        if ($this->cpuNum === null) {
            if (function_exists('swoole_cpu_num')) {
                $this->cpuNum = swoole_cpu_num();
            } else {
                preg_match_all('/^processor/m', file_get_contents('/proc/cpuinfo'), $matches);
                $this->cpuNum = count($matches[0]);
            }
        }

        return $this->cpuNum;
    }

    /**
     * 获取CPU容量
     *
     * @author Verdient。
     */
    protected function getCpuCapacity(): int
    {
        if ($this->cpuCapacity === null) {
            $maxWorkersNumsPerCPU = $this->getMaxWorkersNumsPerCPU();

            if ($maxWorkersNumsPerCPU === 0) {
                $this->cpuCapacity = 0;
            } else {
                $this->cpuCapacity = $maxWorkersNumsPerCPU * $this->getCpuNum();
            }
        }

        return $this->cpuCapacity;
    }

    /**
     * 获取最大进程数量
     *
     * @author Verdient。
     */
    protected function maxNum(): int
    {
        $nums = [];

        $cpuCapacity = $this->getCpuCapacity();

        if ($cpuCapacity > 0) {
            $nums[] = $cpuCapacity;
        }

        if ($this->maxWorkersNums > 0) {
            $nums[] = $this->maxWorkersNums;
        }

        $count = $this->count();

        $availableMemoryCapacity = $this->availableMemoryCapacity();

        $nums[] = $count + $availableMemoryCapacity;

        return min($nums);
    }

    /**
     * 获取可用的进程数量
     *
     * @author Verdient。
     */
    protected function available(): int
    {
        return $this->maxNum() - $this->count() + $this->idleWorkers->count();
    }

    /**
     * 设置最大工作进程数量
     *
     * @param int $value 数量（如果设置为0，则不限制）
     *
     * @author Verdient。
     */
    public function setMaxWorkersNums(int $value): static
    {
        if ($value < 0) {
            throw new InvalidArgumentException('The maximum number of workers must be greater than or equal to 0.');
        }

        $this->maxWorkersNums = $value;

        return $this;
    }

    /**
     * 获取最大工作进程数量
     *
     * @author Verdient。
     */
    public function getMaxWorkersNums(): int
    {
        return $this->maxWorkersNums;
    }

    /**
     * 设置最大空闲时间
     *
     * @param int $value 时间（如果设置为0，则工作进程在处理完任务后立即退出）
     *
     * @author Verdient。
     */
    public function setMaxIdleSeconds(int $value): static
    {
        if ($value < 0) {
            throw new InvalidArgumentException('The maximum idle time must be greater than or equal to 0.');
        }

        $this->maxIdleSeconds = $value;

        return $this;
    }

    /**
     * 获取最大空闲时间
     *
     * @author Verdient。
     */
    public function getMaxIdleSeconds(): int
    {
        return $this->maxIdleSeconds;
    }

    /**
     * 设置名称
     *
     * @param string $name 名称
     *
     * @author Verdient。
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 获取名称
     *
     * @author Verdient。
     */
    public function getName(): string
    {
        if ($this->name === null) {
            $this->name = PHP_BINARY . ' ' . $this->identifier . ' Task Worker';
        }

        return $this->name;
    }

    /**
     * 设置单个CPU可以承载的进程数量
     *
     * @param ?int $num 数量，设置为Null则表示不限制
     *
     * @author Verdient。
     */
    public function setMaxWorkersNumsPerCPU(int $num): static
    {
        if ($num < 0) {
            throw new InvalidArgumentException('The maximum number of workers per CPU must be greater than or equal to 0.');
        }

        $this->maxWorkersNumsPerCPU = $num;

        return $this;
    }

    /**
     * 获取单个CPU可以承载的进程数量
     *
     * @author Verdient。
     */
    public function getMaxWorkersNumsPerCPU(): int
    {
        return $this->maxWorkersNumsPerCPU;
    }

    /**
     * 设置进程驱动
     *
     * @param ProcessDriverInterface $processDriver 进程驱动
     *
     * @author Verdient。
     */
    public function setProcessDriver(ProcessDriverInterface $processDriver): static
    {
        $this->processDriver = $processDriver;

        return $this;
    }

    /**
     * 获取进程驱动
     *
     * @author Verdient。
     */
    public function getProcessDriver(): ProcessDriverInterface
    {
        if ($this->processDriver === null) {
            if (class_exists('Swoole\Process')) {
                $this->processDriver = new SwooleDriver;
            } else if (function_exists('pcntl_fork')) {
                $this->processDriver = new ForkDriver;
            } else if (function_exists('proc_open')) {
                $this->processDriver = new ProcOpenDriver;
            } else {
                throw new RuntimeException('No process driver available.');
            }
        }
        return $this->processDriver;
    }

    /**
     * 设置工作进程启动处理程序
     *
     * @param ?HandlerInterface $handler 处理程序
     *
     * @author Verdient。
     */
    public function setBootHandler(?HandlerInterface $handler): static
    {
        $this->bootHandler = $handler;
        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function dispatch(): void
    {
        $this->registerSignal();

        $nextProducibleAt = 0;

        $this->onStart();

        while (true) {

            if ($this->shouldExit()) {
                $this->onStop();
                error_reporting(E_ALL & ~E_NOTICE);
                pcntl_signal(SIGTERM, SIG_DFL);
                posix_kill(getmypid(), SIGTERM);
                break;
            }

            $notRunningWorkers = [];

            foreach ($this->workers->getIterator() as $worker) {
                $worker->flush();

                if (!$worker->isRunning()) {
                    $notRunningWorkers[] = $worker;
                    continue;
                }

                if ($worker->isIdle()) {
                    $this->setIdle($worker);
                }
            }

            foreach ($notRunningWorkers as $worker) {
                $this->remove($worker);
            }

            $isPushed = false;

            if (microtime(true) >= $nextProducibleAt) {
                if ($this->pushable()) {
                    $data = $this->produce();
                    if ($data === null) {
                        $idleGap = $this->idleGap();
                        $nextProducibleAt = microtime(true) + max(0, (float) $idleGap);
                    } else {
                        $this->push(new Packet($data));
                        $nextProducibleAt = 0;
                        $isPushed = true;
                    }
                }
            }

            if (!$isPushed) {
                $sleepSeconds = $nextProducibleAt - microtime(true);
                if ($sleepSeconds > 0) {
                    usleep((int) ($sleepSeconds * 1000000));
                } else {
                    usleep((int) (100000));
                }
            }
        }
    }
}
