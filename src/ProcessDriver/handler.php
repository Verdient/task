<?php

declare(strict_types=1);

if (count($argv) !== 4) {
    throw new InvalidArgumentException('Invalid arguments.');
}

require $argv[3] . "/vendor/autoload.php";

use Verdient\Task\Process\ChildrenProcess;
use Verdient\Task\Socket;
use Verdient\Task\Stream;

$driverPath = $argv[1];

$handlerPath = $argv[2];

$driver = unserialize(file_get_contents($driverPath));

@unlink($driverPath);

$driver->socket = new Socket(new Stream(fopen('php://fd/3', 'r+')));

$handler = unserialize(file_get_contents($handlerPath));

@unlink($handlerPath);

$handler->handle(new ChildrenProcess($driver));
