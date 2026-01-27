<?php

declare(strict_types=1);

namespace Verdient\Task;

use Stringable;
use Verdient\Task\StreamInterface;

/**
 * Socket
 *
 * @author Verdient。
 */
class Socket
{
    /**
     * 单条消息允许的最大长度（字节），防止内存被恶意耗尽或解析错误造成 OOM
     *
     * @author Verdient。
     */
    const MAX_MESSAGE_SIZE = 10485760;

    /**
     * 缓存的已读取的消息
     *
     * @author Verdient。
     */
    protected string $cachedReads = '';

    /**
     * 是否已关闭
     *
     * @author Verdient。
     */
    protected bool $isClosed = false;

    /**
     * 流
     *
     * @author Verdient。
     */
    protected StreamInterface $stream;

    /**
     * @param StreamInterface $stream 流
     *
     * @author Verdient。
     */
    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    /**
     * 写数据
     *
     * @param string|Stringable $content 消息内容
     *
     * @author Verdient。
     */
    public function write(string|Stringable $content): bool
    {
        if ($content instanceof Stringable) {
            $content = $content->__toString();
        }

        $bodyLength = strlen($content);

        if ($bodyLength > static::MAX_MESSAGE_SIZE) {
            return false;
        }

        $message = pack('N', $bodyLength) . $content;
        $length = strlen($message);
        $written = 0;
        $attempts = 0;

        try {
            while ($written < $length) {
                if (++$attempts > 10000) {
                    return false;
                }

                $slice = substr($message, $written);
                $result = $this->stream->write($slice);

                if ($result === false) {
                    return false;
                }

                if ($result === 0) {
                    usleep(1000);
                    continue;
                }

                $written += $result;
            }

            return $written === $length;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * 读取数据
     *
     * @author Verdient。
     */
    public function read(): ?string
    {
        try {
            $content = $this->stream->read();
        } catch (\Throwable) {
            $content = false;
        }

        if ($content === '') {
            $this->isClosed = true;
            return null;
        }

        if ($content === false) {
            return null;
        }

        $this->cachedReads .= $content;

        if (strlen($this->cachedReads) < 4) {
            return null;
        }

        $lenData = substr($this->cachedReads, 0, 4);
        $unpacked = unpack('Nlen', $lenData);

        if ($unpacked === false || !isset($unpacked['len'])) {
            $this->cachedReads = '';
            return null;
        }

        $bodyLen = (int) $unpacked['len'];

        if ($bodyLen < 0 || $bodyLen > static::MAX_MESSAGE_SIZE) {
            $this->cachedReads = '';
            return null;
        }

        $totalNeeded = 4 + $bodyLen;
        if (strlen($this->cachedReads) < $totalNeeded) {
            return null;
        }

        $message = substr($this->cachedReads, 4, $bodyLen);

        $this->cachedReads = substr($this->cachedReads, $totalNeeded) ?: '';

        return $message;
    }

    /**
     * 获取连接是否已关闭
     *
     * @author Verdient。
     */
    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    /**
     * 获取流
     *
     * @author Verdient。
     */
    public function getStream(): StreamInterface
    {
        return $this->stream;
    }
}
