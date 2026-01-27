<?php

declare(strict_types=1);

namespace Verdient\Task;

/**
 * FFI
 *
 * @author Verdient。
 */
class FFI
{
    /**
     * 以只读方式打开文件
     *
     * @author Verdient。
     */
    public const O_RDONLY = 0;

    /**
     * 以只写方式打开文件
     *
     * @author Verdient。
     */
    public const O_WRONLY = 1;

    /**
     * 以读写方式打开文件
     *
     * @author Verdient。
     */
    public const O_RDWR = 2;

    /**
     * 如果文件不存在则创建文件
     *
     * @author Verdient。
     */
    public const O_CREAT = 64;

    /**
     * 如果文件已存在且成功打开，则将其长度截断为0（清空文件内容）
     *
     * @author Verdient。
     */
    public const O_TRUNC = 512;

    /**
     * 以追加方式写入文件，写入的数据会被追加到文件末尾
     *
     * @author Verdient。
     */
    public const O_APPEND = 1024;

    /**
     * FFI
     *
     * @author Verdient。
     */
    protected static ?\FFI $ffi = null;

    /**
     * FFI
     *
     * @return object
     * @author Verdient。
     */
    protected static function FFI(): \FFI
    {
        if (static::$ffi === null) {
            static::$ffi = \FFI::cdef("
                int open(const char *pathname, int flags, ...);
                int close(int fd);
                int dup(int oldfd);
                int dup2(int oldfd, int newfd);
                extern int errno;
                char *strerror(int errnum);
            ");
        }
        return static::$ffi;
    }

    /**
     * 打开文件，返回文件描述符
     *
     * @param string $path 文件路径
     * @param int $flags 打开标志
     * @param int $mode 文件权限（默认0644）
     *
     * @author Verdient。
     */
    public static function open(string $path, int $flags, int $mode = 0644): int|false
    {
        $ffi = static::FFI();

        if (($flags & static::O_CREAT) !== 0) {
            $fd = $ffi->open($path, $flags, $mode);
        } else {
            $fd = $ffi->open($path, $flags);
        }

        return $fd === -1 ? false : $fd;
    }

    /**
     * 关闭文件描述符
     *
     * @param int $fd 文件描述符
     *
     * @author Verdient。
     */
    public static function close(int $fd): bool
    {
        return static::FFI()->close($fd) === 0;
    }

    /**
     * 复制文件描述符
     *
     * @param int $oldfd 原文件描述符
     *
     * @author Verdient。
     */
    public static function dup(int $oldfd): int|false
    {
        $fd = static::FFI()->dup($oldfd);
        return $fd === -1 ? false : $fd;
    }

    /**
     * 重定向文件描述符
     *
     * @param int $oldfd 原文件描述符
     * @param int $newfd 新文件描述符
     *
     * @author Verdient。
     */
    public static function dup2(int $oldfd, int $newfd): bool
    {
        return static::FFI()->dup2($oldfd, $newfd) !== -1;
    }
}
