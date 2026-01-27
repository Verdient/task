<?php

declare(strict_types=1);

namespace Verdient\Task;

use Verdient\Logger\LoggableInterface;

/**
 * 任务接口
 *
 * @author Verdient。
 */
interface TaskInterface extends LoggableInterface
{
    /**
     * 生产消息
     *
     * @author Verdient。
     */
    public function produce(): ?array;

    /**
     * 处理任务
     *
     * @param Payload $payload 载荷
     *
     * @author Verdient。
     */
    public static function consume(Payload $payload): void;

    /**
     * 任务为空时的间隙
     *
     * @author Verdient。
     */
    public function idleGap(): int|float;

    /**
     * 启动时的回调函数
     *
     * @author Verdient。
     */
    public function onStart(): void;

    /**
     * 停止时的回调函数
     *
     * @author Verdient。
     */
    public function onStop(): void;
}
