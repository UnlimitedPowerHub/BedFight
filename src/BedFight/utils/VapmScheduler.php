<?php

declare(strict_types=1);

namespace BedFight\Utils;

use BedFight\Core\BedFight;
use vennv\vapm\Promise;
use vennv\vapm\System;

class VapmScheduler {

    private BedFight $plugin;

    public function __construct(BedFight $plugin) {
        $this->plugin = $plugin;
    }

    public function submit(callable $task, ?callable $callback = null): void {
        $promise = Promise::c($task);
        
        if ($callback !== null) {
            $promise->then(function ($result) use ($callback) {
                $callback(null, $result);
            })->catch(function ($error) use ($callback) {
                $callback($error, null);
            });
        }
    }

    public function runAsync(callable $task): Promise {
        return Promise::c($task);
    }

    public function runAsyncWithCallback(callable $task, callable $callback): void {
        $this->submit($task, $callback);
    }

    public function submitBatch(array $tasks, callable $onComplete): void {
        $promises = [];
        foreach ($tasks as $task) {
            $promises[] = Promise::c($task);
        }
        
        Promise::all($promises)->then(function ($results) use ($onComplete) {
            $onComplete([], $results);
        })->catch(function ($errors) use ($onComplete) {
            $onComplete([$errors], []);
        });
    }

    public function setTimeout(callable $callback, int $timeoutMs): \vennv\vapm\SampleMacro {
        return System::setTimeout($callback, $timeoutMs);
    }

    public function clearTimeout(\vennv\vapm\SampleMacro $macro): void {
        System::clearTimeout($macro);
    }

    public function setInterval(callable $callback, int $intervalMs): \vennv\vapm\SampleMacro {
        return System::setInterval($callback, $intervalMs);
    }

    public function clearInterval(\vennv\vapm\SampleMacro $macro): void {
        System::clearInterval($macro);
    }

    public static function time(string $name = 'default'): void {
        System::time($name);
    }

    public static function timeEnd(string $name = 'default'): void {
        System::timeEnd($name);
    }
}