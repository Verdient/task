<?php

declare(strict_types=1);

namespace Verdient\Task;

use Psr\Log\LoggerInterface;

/**
 * 工具
 *
 * @author Verdient。
 */
class Utils
{
    /**
     * 静默调用
     *
     * @param callable $callable 要执行的方法
     * @param Logger $logger 记录器
     *
     * @author Verdient。
     */
    public static function silentCall(callable $callable, LoggerInterface $logger): mixed
    {
        $originalFd1 = FFI::dup(1);

        $originalFd2 = FFI::dup(2);

        $fd1 = FFI::open('/dev/null', FFI::O_WRONLY);

        if ($fd1 !== -1) {
            FFI::dup2($fd1, 1);
        }

        $tmpFilePath = tempnam(sys_get_temp_dir(), 'php' . PHP_VERSION . '-');

        $fd2 = FFI::open($tmpFilePath, FFI::O_WRONLY);

        if ($fd2 !== -1) {
            FFI::dup2($fd2, 2);
        }

        ob_start();

        try {
            return call_user_func($callable);
        } finally {
            if ($content = ob_get_contents()) {
                $logger->debug(trim($content));
            }

            ob_end_clean();

            if (file_exists($tmpFilePath)) {
                if ($errorContent = file_get_contents($tmpFilePath)) {
                    $errorContent = trim($errorContent);
                    if ($errorContent) {
                        $logger->error($errorContent);
                    }
                }
                unlink($tmpFilePath);
            }

            if ($originalFd1 !== -1) {
                FFI::dup2($originalFd1, 1);
                FFI::close($originalFd1);
            }

            if ($originalFd2 !== -1) {
                FFI::dup2($originalFd2, 2);
                FFI::close($originalFd2);
            }

            if ($fd1 !== -1) {
                FFI::close($fd1);
            }

            if ($fd2 !== -1) {
                FFI::close($fd2);
            }
        }
    }
}
