<?php

declare(strict_types=1);

namespace Verdient\Task;

use Override;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * 空事件调度器
 *
 * @author Verdient。
 */
class NullEventDispatcher implements EventDispatcherInterface
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function dispatch(object $event)
    {
        return $event;
    }
}
