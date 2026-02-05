<?php

declare(strict_types=1);

namespace Koabana\Log;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Fabrique de logger Monolog.
 */
final class LoggerFactory
{
    public function __construct(
        private readonly string $name,
        private readonly string $filePath,
        private readonly Level $level = Level::Debug,
    ) {}

    /**
     * @return LoggerInterface
     */
    public function create(): LoggerInterface
    {
        $log = new Logger($this->name);
        $log->pushHandler(new StreamHandler($this->filePath, $this->level));

        return $log;
    }
}
