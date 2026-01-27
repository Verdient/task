<?php

declare(strict_types=1);

namespace Verdient\Task;

use Override;
use Verdient\Logger\HasLogger;

/**
 * 抽象任务
 *
 * @author Verdient。
 */
abstract class AbstractTask implements TaskInterface
{
    use HasLogger;

    /**
     * @author Verdient。
     */
    #[Override]
    public function idleGap(): int|float
    {
        return 1;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function onStart(): void {}

    /**
     * @author Verdient。
     */
    #[Override]
    public function onStop(): void {}
}
