# Task

Task 是一个**任务执行组件**，用于提供统一、稳定、可控的 **任务生产（Produce）— 调度（Dispatch）— 执行（Consume）** 抽象模型。


## 安装

```bash
composer require verdient/task
```

## 运行环境要求

- PHP ≥ 8.0
- 多进程模式需满足以下扩展之一：
  - `pcntl`
  - `swoole`
  - `proc_open`


## 快速开始
```PHP
<?php

declare(strict_types=1);

use Verdient\Task\AbstractTask;
use Verdient\Task\Dispatcher\WorkersDispatcher;
use Verdient\Task\Payload;

require 'vendor/autoload.php';

class YourAwesomeTask extends AbstractTask
{
    public function produce(): ?array
    {
        // 返回 null 表示当前无任务
        return [1];
    }

    public static function consume(Payload $payload): void
    {
        var_dump($payload->data);
    }
}

WorkersDispatcher::create(new YourAwesomeTask())
    ->dispatch();
```