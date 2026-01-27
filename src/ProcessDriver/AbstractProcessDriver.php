<?php

declare(strict_types=1);

namespace Verdient\Task\ProcessDriver;

use Override;
use Verdient\Task\Process\HandlerInterface;
use Verdient\Task\ProcessDriver\ProcessDriverInterface;

/**
 * 抽象进程驱动
 *
 * @author Verdient。
 */
abstract class AbstractProcessDriver implements ProcessDriverInterface
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function bootHandler(): ?HandlerInterface
    {
        return null;
    }
}
