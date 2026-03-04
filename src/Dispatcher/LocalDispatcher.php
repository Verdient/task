<?php

declare(strict_types=1);

namespace Verdient\Task\Dispatcher;

use Override;
use Psr\Log\LoggerInterface;
use Verdient\Task\Event\BeforeConsume;
use Verdient\Task\Event\Consumed;
use Verdient\Task\Event\FailedToConsume;
use Verdient\Task\Payload;

/**
 * 本地调度器
 *
 * @author Verdient。
 */
class LocalDispatcher extends AbstractDispatcher
{
    /**
     * 在进程内调度
     *
     * @author Verdient。
     */
    protected function dispatchInProcess(LoggerInterface $logger): void
    {
        $task = $this->task;

        while (true) {
            begin:

            if ($this->shouldExit()) {
                $this->onStop();
                error_reporting(E_ALL & ~E_NOTICE);
                return;
            }

            $data = $this->produce();

            if ($data !== null) {
                $startAt = microtime(true);
                $endAt = null;
                try {
                    $payload = new Payload($data, $task);
                    $payload->setLogger($logger);
                    $this->dispatchEvent(new BeforeConsume($task, $payload));
                    $startAt = microtime(true);
                    $task::consume($payload);
                    $endAt = microtime(true);
                    $this->dispatchEvent(new Consumed($task, $payload, $startAt, $endAt));
                } catch (\Throwable $e) {
                    if ($endAt === null) {
                        $endAt = microtime(true);
                    }
                    $logger->error($e);
                    $this->dispatchEvent(new FailedToConsume($task, $payload, $e, $startAt, $endAt));
                }
                goto begin;
            }

            $idleGap = $this->idleGap();

            if ($idleGap > 0) {
                $sleepSeconds = (int) floor($idleGap);

                $sleepMicroseconds = (int) floor(($idleGap - $sleepSeconds) * 1000000);

                if ($sleepSeconds > 0) {
                    sleep($sleepSeconds);
                }

                if ($sleepMicroseconds > 0) {
                    if ($sleepMicroseconds > 1000000) {
                        $sleepMicroseconds = 1000000;
                    }

                    usleep($sleepMicroseconds);
                }
            }
        }
    }

    /**
     * 在协程内调度
     *
     * @param LoggerInterface $logger 日志记录器
     *
     * @author Verdient。
     */
    protected function dispatchInCoroutine(LoggerInterface $logger): void
    {
        $this->runInCoroutine(fn() => $this->dispatchInProcess($logger));
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function dispatch(): void
    {
        $this->registerSignal();

        $this->onStart();

        $logger = $this->getConsumeLogger();

        if ($this->enableCoroutine) {
            $this->dispatchInCoroutine($logger);
        } else {
            $this->dispatchInProcess($logger);
        }
    }
}
