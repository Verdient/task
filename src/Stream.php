<?php

declare(strict_types=1);

namespace Verdient\Task;

use InvalidArgumentException;
use Override;

/**
 * 流
 *
 * @author Verdient。
 */
class Stream implements StreamInterface
{
    /**
     * @param mixed $socket 套接字
     *
     * @author Verdient。
     */
    public function __construct(public readonly mixed $socket) {}

    /**
     * @author Verdient。
     */
    #[Override]
    public function read(): string|false
    {
        return @fread($this->socket, 8192);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function write(string $data): int|false
    {
        return @fwrite($this->socket, $data);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function blocking(bool $blocking): bool
    {
        return stream_set_blocking($this->socket, $blocking);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function timeout(float $timeout): bool
    {
        if ($timeout < 0) {
            throw new InvalidArgumentException('The timeout must be greater than or equal to 0.');
        }

        $seconds = (int) $timeout;
        $microseconds = (int) round(($timeout - $seconds) * 1000000);

        if ($microseconds >= 1000000) {
            $seconds++;
            $microseconds = 0;
        }

        return stream_set_timeout($this->socket, $seconds, $microseconds);
    }
}
