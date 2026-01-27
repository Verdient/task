<?php

declare(strict_types=1);

namespace Verdient\Task;

use Verdient\Logger\HasLogger;

/**
 * 载荷
 *
 * @author Verdient。
 */
class Payload
{
    use HasLogger;

    /**
     * @param array $data 数据
     * @param TaskInterface $task 任务
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly array $data,
        public readonly TaskInterface $task
    ) {}
}
