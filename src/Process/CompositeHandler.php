<?php

declare(strict_types=1);

namespace Verdient\Task\Process;

use Override;

/**
 * 组合处理器
 *
 * @author Verdient。
 */
class CompositeHandler implements HandlerInterface
{
    /**
     * @param array<int,HandlerInterface> $handlers 处理器集合
     *
     * @author Verdient。
     */
    public function __construct(protected array $handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function handle(ChildrenProcess $process): void
    {
        foreach ($this->handlers as $handler) {
            $handler->handle($process);
        }
    }
}
