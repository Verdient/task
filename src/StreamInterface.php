<?php

declare(strict_types=1);

namespace Verdient\Task;

/**
 * 流接口
 *
 * @author Verdient。
 */
interface StreamInterface
{
    /**
     * 读取数据
     *
     * @author Verdient。
     */
    public function read(): string|false;

    /**
     * 写入数据
     *
     * @param string $data 数据
     *
     * @author Verdient。
     */
    public function write(string $data): int|false;

    /**
     * 设置是否阻塞
     *
     * @param bool $blocking 是否阻塞
     *
     * @author Verdient。
     */
    public function blocking(bool $blocking): bool;

    /**
     * 设置超时时间
     *
     * @param float $timeout 超时时间（秒）
     *
     * @author Verdient。
     */
    public function timeout(float $timeout): bool;
}
