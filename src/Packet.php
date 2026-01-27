<?php

declare(strict_types=1);

namespace Verdient\Task;

use Stringable;

/**
 * 任务数据包
 *
 * @author Verdient。
 */
class Packet implements Stringable
{
    /**
     * @param array 数据
     *
     * @author Verdient。
     */
    public function __construct(public readonly array $data) {}

    /**
     * 创建新的实例
     *
     * @param array 数据
     *
     * @author Verdient。
     */
    public static function create(array $data): static
    {
        return new static($data);
    }

    /**
     * 将任务转换为字符串
     *
     * @author Verdient。
     */
    public function __toString()
    {
        return serialize($this);
    }
}
