<?php

declare(strict_types=1);

namespace Verdient\Task\Dispatcher;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Verdient\Task\TaskInterface;

/**
 * 调度器接口
 *
 * @author Verdient。
 */
interface DispatcherInterface
{
    /**
     * 创建新的调度器
     *
     * @param TaskInterface $task 任务
     *
     * @author Verdient。
     */
    public static function create(TaskInterface $task): static;

    /**
     * 启用协程
     *
     * @author Verdient。
     */
    public function enableCoroutine(): static;

    /**
     * 禁用协程
     *
     * @author Verdient。
     */
    public function disableCoroutine(): static;

    /**
     * 设置标识符
     *
     * @param string $value 标识符
     *
     * @author Verdient。
     */
    public function setIdentifier(string $value): static;

    /**
     * 获取标识符
     *
     * @author Verdient。
     */
    public function getIdentifier(): string;

    /**
     * 设置生产记录器
     *
     * @param ?LoggerInterface $value 记录器
     *
     * @author Verdient。
     */
    public function setProduceLogger(?LoggerInterface $value): static;

    /**
     * 获取生产记录器
     *
     * @author Verdient。
     */
    public function getProduceLogger(): ?LoggerInterface;

    /**
     * 设置消费记录器
     *
     * @param ?LoggerInterface $value 记录器
     *
     * @author Verdient。
     */
    public function setConsumeLogger(?LoggerInterface $value): static;

    /**
     * 获取消费记录器
     *
     * @author Verdient。
     */
    public function getConsumeLogger(): ?LoggerInterface;

    /**
     * 设置主进程编号
     *
     * @param ?int $masterPid 主进程编号
     *
     * @author Verdient。
     */
    public function setMasterPid(?int $masterPid): static;

    /**
     * 获取主进程编号
     *
     * @author Verdient。
     */
    public function getMasterPid(): ?int;

    /**
     * 设置事件调度器
     *
     * @param ?EventDispatcherInterface $eventDispatcher 事件调度器
     *
     * @author Verdient。
     */
    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): static;

    /**
     * 获取事件调度器
     *
     * @author Verdient。
     */
    public function getEventDispatcher(): ?EventDispatcherInterface;

    /**
     * 调度
     *
     * @author Verdient。
     */
    public function dispatch(): void;
}
