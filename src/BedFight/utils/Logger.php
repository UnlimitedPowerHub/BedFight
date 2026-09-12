<?php

declare(strict_types=1);

namespace BedFight\Utils;

use pocketmine\plugin\PluginLogger;

class Logger {

    private PluginLogger $logger;
    private string $prefix = "[BedFight] ";

    public function __construct(PluginLogger $logger) {
        $this->logger = $logger;
    }

    public function debug(string $message): void {
        $this->logger->debug($this->prefix . $message);
    }

    public function info(string $message): void {
        $this->logger->info($this->prefix . $message);
    }

    public function notice(string $message): void {
        $this->logger->notice($this->prefix . $message);
    }

    public function warning(string $message): void {
        $this->logger->warning($this->prefix . $message);
    }

    public function error(string $message): void {
        $this->logger->error($this->prefix . $message);
    }

    public function critical(string $message): void {
        $this->logger->critical($this->prefix . $message);
    }

    public function emergency(string $message): void {
        $this->logger->emergency($this->prefix . $message);
    }

    public function alert(string $message): void {
        $this->logger->alert($this->prefix . $message);
    }
}