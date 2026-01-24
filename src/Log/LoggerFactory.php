<?php

declare(strict_types=1);

namespace Koabana\Log;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    public function __construct(
        private readonly string $name,
        private readonly string $filePath,
        private readonly Level $level = Level::Debug,
    ) {}

    public function create(): LoggerInterface
    {
        $log = new Logger($this->name);
        $log->pushHandler(new StreamHandler($this->filePath, $this->level));

        return $log;
    }
}
